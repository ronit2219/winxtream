<?php
require_once __DIR__ . '/auth_functions.php';
require_once __DIR__ . '/db_connect.php'; // Ensure database connection is included

// Check if user is logged in, redirect to login if not
if (!isLoggedIn()) {
    header('Location: /Home-Page/login.php');
    exit;
}

// Get current user's data - force refresh from database
$user = getCurrentUser(true);

// Get the wallet balance directly from the database for consistency
$wallet_balance = 1000; // Default balance

// First check if we have a proper database connection
if (isset($conn) && $conn) {
    try {
        // Query the wallets table to get the balance
        $stmt = $conn->prepare("SELECT w.balance FROM wallets w WHERE w.user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $walletData = $result->fetch_assoc();
        $wallet_balance = $walletData ? floatval($walletData['balance']) : 1000;
        $stmt->close();

        // Make sure wallet balance is properly formatted
        if (empty($wallet_balance) || !is_numeric($wallet_balance)) {
            // Fix invalid balance
            error_log("Invalid wallet balance detected for user ID: {$_SESSION['user_id']}. Resetting to 1000.");
            
            // Update in database
            $stmt = $conn->prepare("UPDATE wallets SET balance = 1000 WHERE user_id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $wallet_balance = 1000;
            $stmt->close();
        }
    } catch (Exception $e) {
        error_log("Error retrieving wallet balance: " . $e->getMessage());
        $wallet_balance = 1000; // Default on error
    }
} else {
    // If database connection failed, use session or default
    $wallet_balance = isset($_SESSION['balance']) ? $_SESSION['balance'] : 1000;
    error_log("Database connection not available in game_header.php. Using fallback wallet balance.");
}

// Define the current game based on the folder
$current_script = $_SERVER['SCRIPT_NAME'];

$is_roulette = strpos($current_script, 'roulette') !== false;
$is_mines = strpos($current_script, 'mines') !== false;
$is_crash = strpos($current_script, 'crash-game') !== false;
$is_color_trading = strpos($current_script, 'ColorTrad') !== false;

// Handle game balance updates via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'update_balance') {
        // Validate data
        if (!isset($_POST['amount'], $_POST['type'], $_POST['game'])) {
            echo json_encode(['status' => false, 'message' => 'Missing parameters']);
            exit;
        }
        
        $amount = (float) $_POST['amount'];
        $type = $_POST['type'];
        $game = $_POST['game'];
        
        error_log("Balance update request: Amount: $amount, Type: $type, Game: $game, Current balance: {$_SESSION['balance']}");
        
        // Check if user has enough funds for negative amount
        if ($amount < 0 && abs($amount) > $_SESSION['balance']) {
            echo json_encode(['status' => false, 'message' => 'Not enough funds']);
            exit;
        }
        
        // Update balance in database
        $result = updateUserBalance($amount, $type, $game);
        
        if ($result) {
            // If win/loss, record game session
            if ($type === 'win' || $type === 'loss') {
                $bet_amount = isset($_POST['bet_amount']) ? (float) $_POST['bet_amount'] : 0;
                $multiplier = isset($_POST['multiplier']) ? (float) $_POST['multiplier'] : 1;
                $profit = $amount;
                
                recordGameSession($game, $bet_amount, $multiplier, $profit, $type === 'win' ? 'win' : 'loss');
            }
            
            error_log("Balance update successful. New balance: {$_SESSION['balance']}");
            
            echo json_encode([
                'status' => true, 
                'message' => 'Balance updated successfully',
                'new_balance' => $_SESSION['balance']
            ]);
        } else {
            error_log("Balance update failed.");
            echo json_encode(['status' => false, 'message' => 'Failed to update balance']);
        }
        exit;
    }
}
?> 