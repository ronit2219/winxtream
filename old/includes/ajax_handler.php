<?php
// Ajax handler for winxtream platform
require_once 'auth_functions.php';
require_once 'db_connect.php';

// Enable detailed error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log all requests for debugging
error_log("AJAX Request received: " . json_encode($_POST));

// Set headers for JSON response
header('Content-Type: application/json');

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Only POST requests are allowed']);
    exit;
}

// Get the current user with error handling
try {
    $user = getCurrentUser(true); // Force refresh from database
    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'User not logged in']);
        exit;
    }
} catch (Exception $e) {
    error_log("Error getting current user: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error getting user session: ' . $e->getMessage()]);
    exit;
}

// Handle different actions
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'update_balance':
            handleBalanceUpdate();
            break;
        case 'get_balance':
            getWalletBalance();
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action]);
            break;
    }
} catch (Exception $e) {
    error_log("Error processing AJAX action '$action': " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}

/**
 * Get the current wallet balance from the database
 */
function getWalletBalance() {
    global $conn, $user;
    
    try {
        // Get the latest balance from the database
        $stmt = $conn->prepare("SELECT w.balance FROM wallets w WHERE w.user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $walletData = $result->fetch_assoc();
            
            if ($walletData) {
                echo json_encode([
                    'success' => true,
                    'balance' => floatval($walletData['balance']),
                    'message' => 'Balance retrieved successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Wallet data not found'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Database error: ' . $conn->error
            ]);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error in getWalletBalance: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Error retrieving wallet balance: ' . $e->getMessage()
        ]);
    }
}

/**
 * Handle wallet balance updates
 */
function handleBalanceUpdate() {
    global $conn, $user;
    
    try {
        // This method handles two types of requests:
        // 1. Direct balance update (new_balance parameter)
        // 2. Change amount update (amount parameter - legacy format)
        
        // Get parameters
        $newBalance = isset($_POST['new_balance']) ? floatval($_POST['new_balance']) : null;
        $changeAmount = isset($_POST['change_amount']) ? floatval($_POST['change_amount']) : 
                       (isset($_POST['amount']) ? floatval($_POST['amount']) : 0); // Support both formats
        
        $game = $_POST['game'] ?? 'unknown';
        $betAmount = isset($_POST['bet_amount']) ? floatval($_POST['bet_amount']) : 0;
        $multiplier = isset($_POST['multiplier']) ? floatval($_POST['multiplier']) : 1;
        $result = $_POST['result'] ?? ($changeAmount >= 0 ? 'win' : 'loss');
        
        // Log balance update attempt
        error_log("Balance update: Current session user_id=" . ($_SESSION['user_id'] ?? 'not set') . 
                ", new_balance=$newBalance, change_amount=$changeAmount, game=$game");
        
        // If we have a change amount but no new balance, calculate the new balance
        if ($newBalance === null && $changeAmount !== 0) {
            // Get current balance
            $stmt = $conn->prepare("SELECT balance FROM wallets WHERE user_id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $walletData = $result->fetch_assoc();
            
            if ($walletData) {
                $currentBalance = floatval($walletData['balance']);
                $newBalance = $currentBalance + $changeAmount;
            } else {
                echo json_encode(['success' => false, 'error' => 'Could not retrieve current balance']);
                exit;
            }
            $stmt->close();
        }
        
        // Validate parameters
        if ($newBalance === null || $newBalance < 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid balance value']);
            exit;
        }
        
        // Update user's balance in the database
        $stmt = $conn->prepare("UPDATE wallets SET balance = ? WHERE user_id = ?");
        $stmt->bind_param("di", $newBalance, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            // Also update session for backup
            $_SESSION['balance'] = $newBalance;
            
            // Log the transaction
            try {
                logTransaction($_SESSION['user_id'], $changeAmount, $game, $betAmount, $multiplier);
            } catch (Exception $e) {
                error_log("Warning: Transaction logging failed but balance was updated: " . $e->getMessage());
                // Continue since balance was updated successfully
            }
            
            // Return success response
            echo json_encode([
                'success' => true,
                'new_balance' => $newBalance,
                'message' => 'Balance updated successfully'
            ]);
        } else {
            error_log("Database error updating balance: " . $conn->error);
            // Return error response
            echo json_encode([
                'success' => false,
                'error' => 'Database error: ' . $conn->error
            ]);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error in handleBalanceUpdate: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Error updating balance: ' . $e->getMessage()
        ]);
    }
}

/**
 * Log a transaction in the database
 */
function logTransaction($userId, $amount, $game, $betAmount, $multiplier) {
    global $conn;
    
    try {
        // First, get the user's wallet_id
        $stmt = $conn->prepare("SELECT wallet_id FROM wallets WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $row = $result->fetch_assoc()) {
            $walletId = $row['wallet_id'];
            $stmt->close();
            
            // Determine transaction type based on amount
            $type = $amount >= 0 ? 'win' : 'loss';
            
            // Create transactions table if it doesn't exist with proper structure
            $createTable = "CREATE TABLE IF NOT EXISTS transactions (
                transaction_id INT AUTO_INCREMENT PRIMARY KEY,
                wallet_id INT NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                type ENUM('deposit', 'withdrawal', 'win', 'loss') NOT NULL,
                game VARCHAR(20) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (wallet_id) REFERENCES wallets(wallet_id) ON DELETE CASCADE
            )";
            
            $conn->query($createTable);
            
            // Insert transaction log with correct columns
            $stmt = $conn->prepare("INSERT INTO transactions (wallet_id, amount, type, game) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("idss", $walletId, $amount, $type, $game);
            $stmt->execute();
            $stmt->close();
            
            return true;
        } else {
            // Log error and return false
            error_log("Could not find wallet_id for user_id: $userId");
            throw new Exception("Wallet not found for user_id: $userId");
        }
    } catch (Exception $e) {
        error_log("Error in logTransaction: " . $e->getMessage());
        throw $e; // Re-throw to allow higher-level handling
    }
}
?> 