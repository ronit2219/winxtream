<?php
// Add this code at the very top to debug withdrawal requests
if (isset($_GET['debug_withdrawals'])) {
    echo "<h1>Debugging Withdrawal Requests</h1>";
    
    require_once '../includes/db_connect.php';
    
    try {
        // Check if table exists
        $checkTable = $pdo->query("SHOW TABLES LIKE 'withdrawal_requests'");
        echo "<p>Table check result: " . $checkTable->rowCount() . " (should be 1 if table exists)</p>";
        
        // Count requests
        $count = $pdo->query("SELECT COUNT(*) FROM withdrawal_requests")->fetchColumn();
        echo "<p>Total withdrawal requests: $count</p>";
        
        // Get database name
        $dbname = $pdo->query("SELECT DATABASE()")->fetchColumn();
        echo "<p>Current database: $dbname</p>";
        
        // Show all requests
        $requests = $pdo->query("SELECT wr.*, u.username FROM withdrawal_requests wr 
                              LEFT JOIN users u ON wr.user_id = u.user_id
                              ORDER BY wr.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Found " . count($requests) . " requests</p>";
        
        // Display them in a table
        echo "<table border='1' style='width:100%; border-collapse: collapse;'>";
        echo "<tr style='background-color:#333; color:white;'><th>ID</th><th>User ID</th><th>Username</th><th>Amount</th><th>UPI ID</th><th>Status</th><th>Created</th></tr>";
        
        foreach ($requests as $req) {
            echo "<tr>";
            echo "<td>" . $req['request_id'] . "</td>";
            echo "<td>" . $req['user_id'] . "</td>";
            echo "<td>" . ($req['username'] ?? 'Unknown') . "</td>";
            echo "<td>$" . number_format($req['amount'], 2) . "</td>";
            echo "<td>" . htmlspecialchars($req['upi_id']) . "</td>";
            echo "<td>" . $req['status'] . "</td>";
            echo "<td>" . $req['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        exit("Debugging complete. <a href='admin_dashboard.php'>Return to dashboard</a>");
    } catch (PDOException $e) {
        exit("Error: " . $e->getMessage());
    }
}

session_start();
require_once '../includes/db_connect.php';

// Admin authentication
$admin_authenticated = false;

// Handle login
if (isset($_POST['admin_login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Debug information
    error_log("Admin login attempt: " . $username);
    
    // For admin, use direct comparison
    if ($username === 'admin' && $password === 'admin123') {
        // Create session for admin
        $_SESSION['admin_id'] = 1;
        $_SESSION['admin_username'] = 'admin';
        $admin_authenticated = true;
        error_log("Admin login successful with direct comparison");
    } else {
        // Try database authentication as fallback
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND user_id = 1");
            $stmt->execute([$username]);
            
            if ($stmt->rowCount() > 0) {
                $admin = $stmt->fetch();
                
                // Check password
                if ($password === 'admin123' || password_verify($password, $admin['password'])) {
                    $_SESSION['admin_id'] = $admin['user_id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $admin_authenticated = true;
                    error_log("Admin login successful with database comparison");
                } else {
                    $error = "Invalid password";
                    error_log("Admin login failed: invalid password");
                }
            } else {
                $error = "Invalid username";
                error_log("Admin login failed: invalid username");
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
            error_log("Admin login database error: " . $e->getMessage());
        }
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_username']);
    $admin_authenticated = false;
    header('Location: admin_dashboard.php');
    exit;
}

// Check if admin is logged in
if (isset($_SESSION['admin_id'])) {
    $admin_authenticated = true;
}

// Data fetching functions
function getUsers($pdo) {
    try {
        $stmt = $pdo->query("SELECT u.user_id, u.username, u.email, u.created_at, w.balance 
                             FROM users u
                             JOIN wallets w ON u.user_id = w.user_id
                             ORDER BY u.user_id");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function getTransactions($pdo, $limit = 50) {
    try {
        // Debug information
        error_log("Fetching transactions...");
        
        // Simplified query to ensure no syntax errors
        $sql = "SELECT t.transaction_id, t.wallet_id, u.username, t.amount, t.type, t.game, t.created_at 
                FROM transactions t 
                LEFT JOIN wallets w ON t.wallet_id = w.wallet_id 
                LEFT JOIN users u ON w.user_id = u.user_id 
                ORDER BY t.created_at DESC 
                LIMIT ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$limit]);
        
        $transactions = $stmt->fetchAll();
        $count = count($transactions);
        error_log("Found $count transactions");
        
        if ($count == 0) {
            // Additional debugging - check if transactions exist at all
            $check = $pdo->query("SELECT COUNT(*) FROM transactions");
            $totalCount = $check->fetchColumn();
            error_log("Total transactions in database: $totalCount");
        }
        
        return $transactions;
    } catch (PDOException $e) {
        error_log("Error fetching transactions: " . $e->getMessage());
        return [];
    }
}

function getGameSessions($pdo, $limit = 50) {
    try {
        $stmt = $pdo->prepare("SELECT g.session_id, u.username, g.game, g.bet_amount, g.multiplier, g.profit, g.result, g.created_at
                               FROM game_sessions g
                               JOIN users u ON g.user_id = u.user_id
                               ORDER BY g.created_at DESC
                               LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function getPaymentRequests($pdo, $limit = 50) {
    try {
        // First, check if payment_requests table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'payment_requests'");
        if ($stmt->rowCount() == 0) {
            // Table doesn't exist, create it
            $sql = "CREATE TABLE IF NOT EXISTS payment_requests (
                request_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                utr_number VARCHAR(50) NULL,
                status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                processed_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
            )";
            $pdo->exec($sql);
            error_log("Created payment_requests table");
        } else {
            // Check if processed_at column exists, add it if not
            $columns = $pdo->query("SHOW COLUMNS FROM payment_requests LIKE 'processed_at'");
            if ($columns->rowCount() == 0) {
                $pdo->exec("ALTER TABLE payment_requests ADD COLUMN processed_at TIMESTAMP NULL AFTER status");
                error_log("Added processed_at column to payment_requests table");
            }
        }
        
        // Try to get payment requests with usernames
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    p.request_id, 
                    p.user_id, 
                    u.username, 
                    p.amount, 
                    p.utr_number, 
                    p.status, 
                    p.processed_at, 
                    p.created_at
                FROM 
                    payment_requests p
                JOIN 
                    users u ON p.user_id = u.user_id
                ORDER BY 
                    p.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $results = $stmt->fetchAll();
            
            if (!empty($results)) {
                error_log("Found " . count($results) . " payment requests with usernames");
                return $results;
            }
        } catch (PDOException $innerE) {
            error_log("Error in first payment requests query: " . $innerE->getMessage());
            // Continue to fallback query
        }
        
        // Fallback to direct query if join fails
        $directStmt = $pdo->prepare("SELECT * FROM payment_requests ORDER BY created_at DESC LIMIT ?");
        $directStmt->execute([$limit]);
        $results = $directStmt->fetchAll();
        error_log("Fallback query found " . count($results) . " payment requests");
        
        return $results;
    } catch (PDOException $e) {
        error_log("Error fetching payment requests: " . $e->getMessage());
        return [];
    }
}

function getWithdrawalRequests($pdo, $limit = 50) {
    try {
        // First check if the table exists to avoid errors
        $tableExists = $pdo->query("SHOW TABLES LIKE 'withdrawal_requests'")->rowCount() > 0;
        if (!$tableExists) {
            error_log("Warning: withdrawal_requests table does not exist");
            return [];
        }
        
        // Try a simple query first to see if we get any results
        $countQuery = $pdo->query("SELECT COUNT(*) FROM withdrawal_requests");
        $count = $countQuery->fetchColumn();
        error_log("Found $count withdrawal requests in database");
        
        if ($count == 0) {
            return []; // No need to proceed if there are no records
        }
        
        // Direct query that doesn't rely on complex logic
        $stmt = $pdo->query("
            SELECT 
                w.*,
                u.username
            FROM 
                withdrawal_requests w
            LEFT JOIN 
                users u ON w.user_id = u.user_id
            ORDER BY 
                w.created_at DESC
            LIMIT $limit
        ");
        
        if (!$stmt) {
            error_log("Query failed when retrieving withdrawal requests");
            return [];
        }
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Log found requests
        error_log("Direct withdrawal query found " . count($results) . " withdrawal requests");
        
        return $results;
    } catch (PDOException $e) {
        error_log("Error in getWithdrawalRequests: " . $e->getMessage());
        return [];
    }
}

// Data for dashboard if authenticated
if ($admin_authenticated) {
    $users = getUsers($pdo);
    $transactions = getTransactions($pdo);
    $game_sessions = getGameSessions($pdo);
    $payment_requests = getPaymentRequests($pdo);
    $withdrawal_requests = getWithdrawalRequests($pdo);
    
    // Debug log
    error_log("Admin dashboard loaded with " . count($withdrawal_requests) . " withdrawal requests");
    
    // Calculate statistics
    $total_users = count($users);
    $total_balance = 0;
    foreach ($users as $user) {
        $total_balance += $user['balance'];
    }
    
    $total_wins = 0;
    $total_losses = 0;
    foreach ($transactions as $transaction) {
        if ($transaction['type'] === 'win') {
            $total_wins += $transaction['amount'];
        } elseif ($transaction['type'] === 'loss') {
            $total_losses += abs($transaction['amount']);
        }
    }
}

// Handle user management (add, remove, update)
if (isset($_POST['add_user']) && $admin_authenticated) {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $initial_balance = floatval($_POST['initial_balance'] ?? 1000);
    
    try {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            if ($user['username'] === $username) {
                $user_error = 'Username already exists';
            } else {
                $user_error = 'Email already exists';
            }
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Begin transaction
            $pdo->beginTransaction();
            
            // Insert the new user
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hashed_password]);
            
            // Get the new user ID
            $userId = $pdo->lastInsertId();
            
            // Create wallet for the user
            $stmt = $pdo->prepare("INSERT INTO wallets (user_id, balance) VALUES (?, ?)");
            $stmt->execute([$userId, $initial_balance]);
            
            // Commit the transaction
            $pdo->commit();
            
            $user_success = 'User added successfully';
            
            // Refresh user list
            $users = getUsers($pdo);
        }
    } catch (PDOException $e) {
        // Rollback the transaction if something failed
        $pdo->rollback();
        $user_error = 'Error adding user: ' . $e->getMessage();
    }
}

// Handle user removal
if (isset($_POST['remove_user']) && $admin_authenticated) {
    $user_id = $_POST['user_id'] ?? 0;
    
    try {
        // Don't allow removing user ID 1 (admin)
        if ($user_id == 1) {
            $user_error = 'Cannot remove admin user';
        } else {
            // Begin transaction
            $pdo->beginTransaction();
            
            // Delete user's transactions
            $stmt = $pdo->prepare("DELETE t FROM transactions t 
                                  JOIN wallets w ON t.wallet_id = w.wallet_id 
                                  WHERE w.user_id = ?");
            $stmt->execute([$user_id]);
            
            // Delete user's wallet
            $stmt = $pdo->prepare("DELETE FROM wallets WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            // Delete user
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            // Commit the transaction
            $pdo->commit();
            
            $user_success = 'User removed successfully';
            
            // Refresh user list
            $users = getUsers($pdo);
        }
    } catch (PDOException $e) {
        // Rollback the transaction if something failed
        $pdo->rollback();
        $user_error = 'Error removing user: ' . $e->getMessage();
    }
}

// Handle wallet balance update
if (isset($_POST['update_wallet']) && $admin_authenticated) {
    $user_id = $_POST['wallet_user_id'] ?? 0;
    $new_balance = floatval($_POST['new_balance'] ?? 0);
    
    try {
        // Get current balance first
        $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $current_balance = $stmt->fetchColumn();
        
        // Calculate change amount
        $change_amount = $new_balance - $current_balance;
        
        // Update wallet balance
        $stmt = $pdo->prepare("UPDATE wallets SET balance = ? WHERE user_id = ?");
        $stmt->execute([$new_balance, $user_id]);
        
        // Log this as an admin transaction with proper type value
        $transaction_type = ($change_amount >= 0) ? 'deposit' : 'withdrawal';
        
        $stmt = $pdo->prepare("INSERT INTO transactions (wallet_id, amount, type, game) 
                              SELECT wallet_id, ?, ?, 'admin'
                              FROM wallets WHERE user_id = ?");
        $stmt->execute([$wallet_id, $change_amount, $transaction_type, $user_id]);
        
        $wallet_success = 'Wallet balance updated successfully';
        
        // Refresh user list
        $users = getUsers($pdo);
        // Refresh transactions list
        $transactions = getTransactions($pdo);
    } catch (PDOException $e) {
        $wallet_error = 'Error updating wallet: ' . $e->getMessage();
    }
}

// Handle transaction deletion
if (isset($_POST['delete_transaction']) && $admin_authenticated) {
    $transaction_id = $_POST['transaction_id'] ?? 0;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM transactions WHERE transaction_id = ?");
        $stmt->execute([$transaction_id]);
        
        $transaction_success = 'Transaction deleted successfully';
        
        // Refresh transactions list
        $transactions = getTransactions($pdo);
    } catch (PDOException $e) {
        $transaction_error = 'Error deleting transaction: ' . $e->getMessage();
    }
}

// Handle payment request approval/rejection
if (isset($_POST['approve_payment']) && $admin_authenticated) {
    $request_id = intval($_POST['request_id'] ?? 0);
    $user_id = intval($_POST['user_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // 1. Check if request exists and is still pending
        $checkStmt = $pdo->prepare("SELECT status FROM payment_requests WHERE request_id = ? AND user_id = ?");
        $checkStmt->execute([$request_id, $user_id]);
        $status = $checkStmt->fetchColumn();
        
        if ($status === false) {
            throw new PDOException("Payment request not found or no longer exists");
        }
        
        if ($status !== 'pending') {
            throw new PDOException("This payment request has already been " . $status);
        }
        
        // 2. Mark request as approved with timestamp
        $updateStmt = $pdo->prepare("UPDATE payment_requests SET status = 'approved', processed_at = NOW() WHERE request_id = ?");
        $updateStmt->execute([$request_id]);
        
        // 3. Get wallet ID
        $walletStmt = $pdo->prepare("SELECT wallet_id FROM wallets WHERE user_id = ?");
        $walletStmt->execute([$user_id]);
        $wallet_id = $walletStmt->fetchColumn();
        
        if (!$wallet_id) {
            throw new PDOException("Wallet not found for user ID " . $user_id);
        }
        
        // 4. Create transaction record
        $transStmt = $pdo->prepare("INSERT INTO transactions (wallet_id, amount, type, game) VALUES (?, ?, 'deposit', 'payment')");
        $transStmt->execute([$wallet_id, $amount]);
        
        // 5. Update wallet balance
        $balanceStmt = $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE wallet_id = ?");
        $balanceStmt->execute([$amount, $wallet_id]);
        
        // 6. Get updated balance for confirmation
        $newBalanceStmt = $pdo->prepare("SELECT balance FROM wallets WHERE wallet_id = ?");
        $newBalanceStmt->execute([$wallet_id]);
        $newBalance = $newBalanceStmt->fetchColumn();
        
        // Commit all changes
        $pdo->commit();
        
        // Success message with details
        $payment_success = "Payment request of $" . number_format($amount, 2) . " approved successfully. New balance: $" . number_format($newBalance, 2);
        
        // Log for debugging
        error_log("Payment request #$request_id approved. Amount: $amount, New balance: $newBalance");
        
        // Refresh data
        $payment_requests = getPaymentRequests($pdo);
        $users = getUsers($pdo);
        $transactions = getTransactions($pdo);
    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $payment_error = 'Error approving payment: ' . $e->getMessage();
        error_log("Payment approval error: " . $e->getMessage());
    }
}

if (isset($_POST['reject_payment']) && $admin_authenticated) {
    $request_id = $_POST['request_id'] ?? 0;
    
    try {
        // Update payment request status with processed timestamp
        $stmt = $pdo->prepare("UPDATE payment_requests SET status = 'rejected', processed_at = NOW() WHERE request_id = ?");
        $stmt->execute([$request_id]);
        
        $payment_success = 'Payment request rejected successfully';
        
        // Refresh payment requests
        $payment_requests = getPaymentRequests($pdo);
    } catch (PDOException $e) {
        $payment_error = 'Error rejecting payment: ' . $e->getMessage();
    }
}

// Handle payment requests refresh
if (isset($_POST['refresh_payments']) && $admin_authenticated) {
    try {
        // Check if payment_requests table exists and create it if it doesn't
        $checkTable = $pdo->query("SHOW TABLES LIKE 'payment_requests'");
        if ($checkTable->rowCount() == 0) {
            $sql = "CREATE TABLE IF NOT EXISTS payment_requests (
                request_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                utr_number VARCHAR(50) NULL,
                status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                processed_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
            )";
            $pdo->exec($sql);
            $payment_success = 'Payment requests table created successfully. Ready to receive deposit requests.';
        } else {
            // Just refresh the data
            $payment_requests = getPaymentRequests($pdo);
            $payment_success = 'Payment requests refreshed successfully.';
        }
    } catch (PDOException $e) {
        $payment_error = 'Error refreshing payment requests: ' . $e->getMessage();
        error_log("Error refreshing payment requests: " . $e->getMessage());
    }
}

// Handle manual cleanup of duplicate payment requests
if (isset($_POST['cleanup_duplicates']) && $admin_authenticated) {
    try {
        $pdo->beginTransaction();
        
        // Find duplicates based on user_id, amount, and utr_number
        $stmt = $pdo->query("
            SELECT user_id, amount, utr_number, COUNT(*) as count, MIN(request_id) as keep_id
            FROM payment_requests 
            GROUP BY user_id, amount, utr_number
            HAVING COUNT(*) > 1
        ");
        
        $duplicates = $stmt->fetchAll();
        $deleted = 0;
        
        foreach ($duplicates as $dup) {
            // Delete all duplicates except the one with the lowest ID
            $deleted += $pdo->exec("
                DELETE FROM payment_requests 
                WHERE user_id = {$dup['user_id']} 
                AND amount = {$dup['amount']} 
                AND utr_number = '{$dup['utr_number']}'
                AND request_id != {$dup['keep_id']}
            ");
        }
        
        $pdo->commit();
        
        if ($deleted > 0) {
            $payment_success = "Successfully removed $deleted duplicate payment request(s)";
        } else {
            $payment_success = "No duplicate payment requests found";
        }
        
        // Refresh payment requests
        $payment_requests = getPaymentRequests($pdo);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $payment_error = 'Error cleaning up duplicates: ' . $e->getMessage();
    }
}

// Handle withdrawal approval
if (isset($_POST['approve_withdrawal']) && $admin_authenticated) {
    $request_id = $_POST['request_id'] ?? 0;
    $user_id = $_POST['user_id'] ?? 0;
    $amount = floatval($_POST['amount'] ?? 0);
    $utr = trim($_POST['utr_number'] ?? '');
    
    if (empty($utr)) {
        $withdrawal_error = 'UTR number is required for withdrawal approval.';
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // 1. Check if request exists and is still pending
            $checkStmt = $pdo->prepare("SELECT status FROM withdrawal_requests WHERE request_id = ? AND user_id = ?");
            $checkStmt->execute([$request_id, $user_id]);
            $status = $checkStmt->fetchColumn();
            
            if ($status !== 'pending') {
                throw new PDOException("This withdrawal request has already been " . $status);
            }
            
            // 2. Update request status and add UTR
            $updateStmt = $pdo->prepare("
                UPDATE withdrawal_requests 
                SET status = 'approved', 
                    processed_at = NOW(),
                    utr_number = ?
                WHERE request_id = ?
            ");
            $updateStmt->execute([$utr, $request_id]);
            
            // 3. Deduct from user's wallet
            $walletStmt = $pdo->prepare("
                UPDATE wallets 
                SET balance = balance - ? 
                WHERE user_id = ? AND balance >= ?
            ");
            $walletStmt->execute([$amount, $user_id, $amount]);
            
            if ($walletStmt->rowCount() === 0) {
                throw new PDOException("Insufficient balance or wallet not found");
            }
            
            // 4. Add transaction record
            $transStmt = $pdo->prepare("
                INSERT INTO transactions (wallet_id, amount, type, game) 
                SELECT wallet_id, ?, 'withdrawal', 'withdrawal' 
                FROM wallets 
                WHERE user_id = ?
            ");
            $transStmt->execute([-$amount, $user_id]);
            
            $pdo->commit();
            $withdrawal_success = "Withdrawal request of $" . number_format($amount, 2) . " approved successfully.";
            
            // Get fresh withdrawal requests data
            $stmt = $pdo->query("
                SELECT 
                    w.*,
                    u.username
                FROM 
                    withdrawal_requests w
                LEFT JOIN 
                    users u ON w.user_id = u.user_id
                ORDER BY 
                    w.created_at DESC
            ");
            $withdrawal_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Update session data
            $_SESSION['withdrawal_requests'] = $withdrawal_requests;
            
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $withdrawal_error = 'Error approving withdrawal: ' . $e->getMessage();
            error_log("Withdrawal approval error: " . $e->getMessage());
        }
    }
}

// Handle withdrawal rejection
if (isset($_POST['reject_withdrawal']) && $admin_authenticated) {
    $request_id = $_POST['request_id'] ?? 0;
    $user_id = $_POST['user_id'] ?? 0;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE withdrawal_requests 
            SET status = 'rejected', 
                processed_at = NOW() 
            WHERE request_id = ? AND user_id = ?
        ");
        $stmt->execute([$request_id, $user_id]);
        
        $withdrawal_success = 'Withdrawal request rejected successfully.';
        
        // Get fresh withdrawal requests data
        $stmt = $pdo->query("
            SELECT 
                w.*,
                u.username
            FROM 
                withdrawal_requests w
            LEFT JOIN 
                users u ON w.user_id = u.user_id
            ORDER BY 
                w.created_at DESC
        ");
        $withdrawal_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Update session data
        $_SESSION['withdrawal_requests'] = $withdrawal_requests;
        
    } catch (PDOException $e) {
        $withdrawal_error = 'Error rejecting withdrawal: ' . $e->getMessage();
        error_log("Withdrawal rejection error: " . $e->getMessage());
    }
}

// Handle withdrawal requests refresh
if (isset($_POST['refresh_withdrawals']) && $admin_authenticated) {
    try {
        // Check if withdrawal_requests table exists and create it if it doesn't
        $checkTable = $pdo->query("SHOW TABLES LIKE 'withdrawal_requests'");
        if ($checkTable->rowCount() == 0) {
            $sql = "CREATE TABLE IF NOT EXISTS withdrawal_requests (
                request_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                upi_id VARCHAR(100) NOT NULL,
                utr_number VARCHAR(50) NULL,
                status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                admin_note TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                processed_at TIMESTAMP NULL,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
            )";
            $pdo->exec($sql);
            $withdrawal_success = 'Withdrawal requests table created successfully. Ready to receive withdrawal requests.';
        } else {
            // Force direct query to get the latest data
            $stmt = $pdo->query("
                SELECT 
                    w.*,
                    u.username
                FROM 
                    withdrawal_requests w
                LEFT JOIN 
                    users u ON w.user_id = u.user_id
                ORDER BY 
                    w.created_at DESC
            ");
            
            $withdrawal_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $count = count($withdrawal_requests);
            
            // Store the result in the session to persist across page loads
            $_SESSION['withdrawal_requests'] = $withdrawal_requests;
            
            // Log the refresh operation
            error_log("Manually refreshed withdrawal requests. Found: $count");
            
            $withdrawal_success = "Withdrawal requests refreshed successfully. Found $count requests.";
        }
    } catch (PDOException $e) {
        $withdrawal_error = 'Error refreshing withdrawal requests: ' . $e->getMessage();
        error_log("Error refreshing withdrawal requests: " . $e->getMessage());
    }
}

// Make sure withdrawal_requests are loaded at the start
if ($admin_authenticated) {
    // Check if we have cached withdrawal requests in session
    if (isset($_SESSION['withdrawal_requests']) && !empty($_SESSION['withdrawal_requests'])) {
        $withdrawal_requests = $_SESSION['withdrawal_requests'];
        error_log("Loaded " . count($withdrawal_requests) . " withdrawal requests from session");
    } else {
        // Get fresh data from database
        $withdrawal_requests = getWithdrawalRequests($pdo);
        
        // Store in session for persistence
        if (!empty($withdrawal_requests)) {
            $_SESSION['withdrawal_requests'] = $withdrawal_requests;
            error_log("Stored " . count($withdrawal_requests) . " withdrawal requests in session");
        }
    }
}

// Handle creating withdrawal requests table manually
if (isset($_POST['create_withdrawal_table']) && $admin_authenticated) {
    try {
        $sql = "CREATE TABLE IF NOT EXISTS withdrawal_requests (
            request_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            upi_id VARCHAR(100) NOT NULL,
            utr_number VARCHAR(50) NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            admin_note TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            processed_at TIMESTAMP NULL,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )";
        $pdo->exec($sql);
        $withdrawal_success = 'Withdrawal requests table created successfully. Ready to receive withdrawal requests.';
        
        // Insert a test withdrawal request
        $stmt = $pdo->prepare("INSERT INTO withdrawal_requests (user_id, amount, upi_id, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([1, 100.00, 'admin@upi', 'pending']);
        $withdrawal_success .= ' Test request created.';
        
        // Refresh withdrawal requests
        $withdrawal_requests = getWithdrawalRequests($pdo);
    } catch (PDOException $e) {
        $withdrawal_error = 'Error creating withdrawal requests table: ' . $e->getMessage();
        error_log("Error creating withdrawal requests table: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WinXtream Admin Dashboard</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #121212;
            color: #e0e0e0;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 1px solid #333;
            padding-bottom: 10px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #7c4dff;
        }
        .login-form {
            background-color: #1e1e1e;
            padding: 30px;
            border-radius: 8px;
            max-width: 400px;
            margin: 100px auto;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background-color: #1e1e1e;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #7c4dff;
            margin-top: 10px;
        }
        .panel {
            background-color: #1e1e1e;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #333;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #333;
        }
        th {
            background-color: #252525;
            font-weight: 600;
        }
        tr:hover {
            background-color: #252525;
        }
        .tabs {
            display: flex;
            margin-bottom: 20px;
        }
        .tab {
            padding: 10px 20px;
            background-color: #252525;
            margin-right: 5px;
            border-radius: 5px 5px 0 0;
            cursor: pointer;
        }
        .tab.active {
            background-color: #7c4dff;
            color: white;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        input, button {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #333;
            border-radius: 4px;
            background-color: #252525;
            color: #e0e0e0;
        }
        button {
            background-color: #7c4dff;
            border: none;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }
        button:hover {
            background-color: #6a3ecf;
        }
        .user-actions a, .logout-btn {
            color: #7c4dff;
            text-decoration: none;
            margin-left: 10px;
        }
        .user-actions a:hover, .logout-btn:hover {
            text-decoration: underline;
        }
        .negative {
            color: #f44336;
        }
        .positive {
            color: #4caf50;
        }
        .pending {
            color: #ffc107;
            font-weight: bold;
        }
        .approved {
            color: #4caf50;
            font-weight: bold;
        }
        .rejected {
            color: #f44336;
            font-weight: bold;
        }
        .success-message {
            background-color: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .error-message {
            background-color: rgba(244, 67, 54, 0.1);
            color: #F44336;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .info-message {
            background-color: rgba(33, 150, 243, 0.1);
            color: #2196F3;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .withdraw-row {
            margin-bottom: 10px;
        }
        input[type="text"] {
            padding: 8px;
            border: 1px solid #333;
            border-radius: 4px;
            background-color: #252525;
            color: #e0e0e0;
        }
        form {
            margin: 0;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #1e1e1e;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 50%;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: #7c4dff;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!$admin_authenticated): ?>
            <!-- Login Form -->
            <div class="login-form">
                <h2>Admin Login</h2>
                <?php if (isset($error)): ?>
                    <p style="color: #f44336;"><?php echo $error; ?></p>
                <?php endif; ?>
                <form method="POST" action="">
                    <div>
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div>
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" name="admin_login">Login</button>
                </form>
            </div>
        <?php else: ?>
            <!-- Admin Dashboard -->
            <div class="header">
                <div class="logo">WinXtream Admin Dashboard</div>
                <div class="user-info">
                    Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>
                    <a href="?logout=1" class="logout-btn">Logout</a>
                </div>
            </div>
            
            <!-- Statistics -->
            <div class="stats-container">
                <div class="stat-card">
                    <div>Total Users</div>
                    <div class="stat-value"><?php echo $total_users; ?></div>
                </div>
                <div class="stat-card">
                    <div>Total Balance</div>
                    <div class="stat-value">$<?php echo number_format($total_balance, 2); ?></div>
                </div>
                <div class="stat-card">
                    <div>Total Wins</div>
                    <div class="stat-value positive">$<?php echo number_format($total_wins, 2); ?></div>
                </div>
                <div class="stat-card">
                    <div>Total Losses</div>
                    <div class="stat-value negative">$<?php echo number_format($total_losses, 2); ?></div>
                </div>
            </div>
            
            <!-- Tabs -->
            <div class="tabs">
                <div class="tab active" onclick="openTab('users-tab')">Users</div>
                <div class="tab" onclick="openTab('wallet-tab')">Wallet Management</div>
                <div class="tab" onclick="openTab('payment-requests-tab')">Payment Requests</div>
                <div class="tab" onclick="openTab('withdrawal-requests-tab')">Withdrawal Requests</div>
                <div class="tab" onclick="openTab('transactions-tab')">Transactions</div>
                <div class="tab" onclick="openTab('game-sessions-tab')">Game Sessions</div>
            </div>
            
            <!-- Users Tab -->
            <div id="users-tab" class="panel tab-content active">
                <div class="panel-header">
                    <h2>User Management</h2>
                    <button onclick="openModal('add-user-modal')" class="btn">Add New User</button>
                </div>
                
                <?php if (isset($user_success)): ?>
                <div class="success-message"><?php echo $user_success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($user_error)): ?>
                <div class="error-message"><?php echo $user_error; ?></div>
                <?php endif; ?>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Balance</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['user_id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>$<?php echo number_format($user['balance'], 2); ?></td>
                            <td><?php echo $user['created_at']; ?></td>
                            <td>
                                <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove this user? This action cannot be undone.');">
                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                    <button type="submit" name="remove_user" class="btn btn-danger" <?php if($user['user_id'] == 1) echo 'disabled'; ?>>Remove</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Wallet Management Tab -->
            <div id="wallet-tab" class="panel tab-content">
                <div class="panel-header">
                    <h2>Wallet Management</h2>
                </div>
                
                <?php if (isset($wallet_success)): ?>
                <div class="success-message"><?php echo $wallet_success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($wallet_error)): ?>
                <div class="error-message"><?php echo $wallet_error; ?></div>
                <?php endif; ?>
                
                <table>
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Current Balance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['user_id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td>$<?php echo number_format($user['balance'], 2); ?></td>
                            <td>
                                <button onclick="openUpdateWalletModal(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>', <?php echo $user['balance']; ?>)" class="btn">Update Balance</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Payment Requests Tab -->
            <div id="payment-requests-tab" class="panel tab-content">
                <div class="panel-header">
                    <h2>Payment Requests</h2>
                    <form method="post" style="display: inline;">
                        <button type="submit" name="refresh_payments" class="btn" style="background-color: #209cee;">Refresh</button>
                    </form>
                </div>
                
                <?php if (isset($payment_success)): ?>
                <div class="success-message"><?php echo $payment_success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($payment_error)): ?>
                <div class="error-message"><?php echo $payment_error; ?></div>
                <?php endif; ?>
                
                <?php 
                // If no payment requests were found with the join approach, try a direct query
                if (empty($payment_requests)) {
                    try {
                        $direct_query = $pdo->query("SELECT DISTINCT * FROM payment_requests ORDER BY created_at DESC LIMIT 50");
                        $direct_payment_requests = $direct_query->fetchAll();
                        if (!empty($direct_payment_requests)) {
                            echo "<div class='info-message'>Using direct payment request data (username information unavailable)</div>";
                            $payment_requests = $direct_payment_requests;
                        }
                    } catch (PDOException $e) {
                        // Silent fail, we'll use the empty array
                        error_log("Error in direct payment request query: " . $e->getMessage());
                    }
                }

                // Check if we have any payment requests at all
                $has_any_requests = !empty($payment_requests);
                
                // Add a message if there are pending payments
                $pending_count = 0;
                if ($has_any_requests) {
                    foreach ($payment_requests as $request) {
                        if ($request['status'] === 'pending') {
                            $pending_count++;
                        }
                    }
                    
                    if ($pending_count > 0) {
                        echo "<div class='info-message'>There are $pending_count pending payment requests that need your attention.</div>";
                    }
                }
                ?>
                
                <?php if (empty($payment_requests)): ?>
                    <div class="info-message" style="margin-top: 20px;">No payment requests found. Users have not made any deposit requests yet.</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Amount</th>
                                <th>UTR Number</th>
                                <th>Status</th>
                                <th>Request Date</th>
                                <th>Processed Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payment_requests as $request): ?>
                            <tr>
                                <td><?php echo $request['request_id']; ?></td>
                                <td>
                                    <?php 
                                    if (isset($request['username'])) {
                                        echo htmlspecialchars($request['username']);
                                    } else {
                                        echo "User ID: " . $request['user_id']; 
                                    }
                                    ?>
                                </td>
                                <td>$<?php echo number_format($request['amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($request['utr_number']); ?></td>
                                <td>
                                    <span class="<?php echo $request['status']; ?>">
                                        <?php echo ucfirst($request['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $request['created_at']; ?></td>
                                <td><?php echo $request['processed_at'] ? $request['processed_at'] : 'Not processed'; ?></td>
                                <td>
                                    <?php if ($request['status'] === 'pending'): ?>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                            <input type="hidden" name="user_id" value="<?php echo $request['user_id']; ?>">
                                            <input type="hidden" name="amount" value="<?php echo $request['amount']; ?>">
                                            <button type="submit" name="approve_payment" class="btn" style="background-color: #4CAF50;">Approve</button>
                                        </form>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                            <button type="submit" name="reject_payment" class="btn" style="background-color: #F44336;">Reject</button>
                                        </form>
                                    <?php else: ?>
                                        <span>No actions available</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Withdrawal Requests Tab -->
            <div id="withdrawal-requests-tab" class="panel tab-content">
                <div class="panel-header">
                    <h2>Withdrawal Requests</h2>
                    <div>
                        <form method="post" style="display: inline;">
                            <button type="submit" name="refresh_withdrawals" class="btn" style="background-color: #209cee;">Refresh</button>
                        </form>
                        <a href="?debug_withdrawals=1" class="btn" style="background-color: #ff9800; margin-left: 5px;">Debug</a>
                    </div>
                </div>
                
                <?php if (isset($withdrawal_success)): ?>
                <div class="success-message"><?php echo $withdrawal_success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($withdrawal_error)): ?>
                <div class="error-message"><?php echo $withdrawal_error; ?></div>
                <?php endif; ?>
                
                <?php 
                // Check if we have any withdrawal requests at all
                $has_any_withdrawal_requests = !empty($withdrawal_requests);
                
                // Add a message if there are pending withdrawals
                $pending_withdrawal_count = 0;
                if ($has_any_withdrawal_requests) {
                    foreach ($withdrawal_requests as $request) {
                        if ($request['status'] === 'pending') {
                            $pending_withdrawal_count++;
                        }
                    }
                    
                    if ($pending_withdrawal_count > 0) {
                        echo "<div class='info-message'>There are $pending_withdrawal_count pending withdrawal requests that need your attention.</div>";
                    }
                }
                ?>
                
                <?php if (empty($withdrawal_requests)): ?>
                    <div class="info-message" style="margin-top: 20px;">No withdrawal requests found. Users have not made any withdrawal requests yet.</div>
                    
                    <div class="error-message">
                        <p><strong>Troubleshooting:</strong></p>
                        <ol>
                            <li>Make sure you've completed the "Debug" button check to verify the withdrawal_requests table exists.</li>
                            <li>Check that users are able to submit withdrawal requests from the user interface.</li>
                            <li>Try submitting a test withdrawal request as a regular user.</li>
                        </ol>
                    </div>
                    
                    <form method="post" style="margin-top: 20px;">
                        <button type="submit" name="create_withdrawal_table" class="btn" style="background-color: #7c4dff;">Create Withdrawal Requests Table</button>
                    </form>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Amount</th>
                                <th>UPI ID</th>
                                <th>Status</th>
                                <th>UTR Number</th>
                                <th>Request Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($withdrawal_requests as $request): ?>
                            <tr>
                                <td><?php echo $request['request_id']; ?></td>
                                <td>
                                    <?php 
                                    if (isset($request['username'])) {
                                        echo htmlspecialchars($request['username']);
                                    } else {
                                        echo "User ID: " . $request['user_id']; 
                                    }
                                    ?>
                                </td>
                                <td>$<?php echo number_format($request['amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($request['upi_id']); ?></td>
                                <td>
                                    <span class="<?php echo $request['status']; ?>">
                                        <?php echo ucfirst($request['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo !empty($request['utr_number']) ? htmlspecialchars($request['utr_number']) : '-'; ?></td>
                                <td><?php echo $request['created_at']; ?></td>
                                <td>
                                    <?php if ($request['status'] === 'pending'): ?>
                                        <form method="post" style="display: inline; margin-bottom: 5px;">
                                            <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                            <input type="hidden" name="user_id" value="<?php echo $request['user_id']; ?>">
                                            <input type="hidden" name="amount" value="<?php echo $request['amount']; ?>">
                                            <input type="text" name="utr_number" placeholder="Enter UTR" required style="width: 100px; display: inline-block; margin-right: 5px;">
                                            <button type="submit" name="approve_withdrawal" class="btn" style="background-color: #4CAF50; width: auto;">Approve</button>
                                        </form>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                            <input type="hidden" name="user_id" value="<?php echo $request['user_id']; ?>">
                                            <button type="submit" name="reject_withdrawal" class="btn" style="background-color: #F44336; width: auto;">Reject</button>
                                        </form>
                                    <?php else: ?>
                                        <span>No actions available</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Transactions Tab -->
            <div id="transactions-tab" class="panel tab-content">
                <div class="panel-header">
                    <h2>Transaction History</h2>
                </div>
                
                <?php if (isset($transaction_success)): ?>
                <div class="success-message"><?php echo $transaction_success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($transaction_error)): ?>
                <div class="error-message"><?php echo $transaction_error; ?></div>
                <?php endif; ?>
                
                <?php 
                // If no transactions were found with the join approach, try a direct query
                if (empty($transactions)) {
                    try {
                        $direct_query = $pdo->query("SELECT * FROM transactions ORDER BY created_at DESC LIMIT 50");
                        $direct_transactions = $direct_query->fetchAll();
                        if (!empty($direct_transactions)) {
                            echo "<div class='info-message'>Using direct transaction data (wallet/user information unavailable)</div>";
                            $transactions = $direct_transactions;
                        }
                    } catch (PDOException $e) {
                        // Silent fail, we'll use the empty array
                    }
                }
                ?>
                
                <?php if (empty($transactions)): ?>
                    <div class="error-message">No transactions found. Make sure transactions have been created in the system.</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Amount</th>
                                <th>Type</th>
                                <th>Game</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?php echo $transaction['transaction_id']; ?></td>
                                <td>
                                    <?php 
                                    if (isset($transaction['username'])) {
                                        echo htmlspecialchars($transaction['username'] ?? "User Deleted");
                                    } else {
                                        echo "User ID: " . $transaction['wallet_id']; 
                                    }
                                    ?>
                                </td>
                                <td class="<?php echo $transaction['amount'] >= 0 ? 'positive' : 'negative'; ?>">
                                    $<?php echo number_format($transaction['amount'], 2); ?>
                                </td>
                                <td><?php echo $transaction['type'] ?: 'N/A'; ?></td>
                                <td><?php echo $transaction['game'] ?: 'N/A'; ?></td>
                                <td><?php echo $transaction['created_at']; ?></td>
                                <td>
                                    <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this transaction? This action cannot be undone.');">
                                        <input type="hidden" name="transaction_id" value="<?php echo $transaction['transaction_id']; ?>">
                                        <button type="submit" name="delete_transaction" class="btn btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Game Sessions Tab -->
            <div id="game-sessions-tab" class="panel tab-content">
                <div class="panel-header">
                    <h2>Game Sessions</h2>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Game</th>
                            <th>Bet</th>
                            <th>Multiplier</th>
                            <th>Profit</th>
                            <th>Result</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($game_sessions as $session): ?>
                        <tr>
                            <td><?php echo $session['session_id']; ?></td>
                            <td><?php echo htmlspecialchars($session['username']); ?></td>
                            <td><?php echo $session['game']; ?></td>
                            <td>$<?php echo number_format($session['bet_amount'], 2); ?></td>
                            <td>x<?php echo $session['multiplier']; ?></td>
                            <td class="<?php echo $session['profit'] >= 0 ? 'positive' : 'negative'; ?>">
                                $<?php echo number_format($session['profit'], 2); ?>
                            </td>
                            <td><?php echo $session['result']; ?></td>
                            <td><?php echo $session['created_at']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Add User Modal -->
            <div id="add-user-modal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal('add-user-modal')">&times;</span>
                    <h2>Add New User</h2>
                    <form method="post">
                        <div>
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" required>
                        </div>
                        <div>
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div>
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <div>
                            <label for="initial_balance">Initial Balance</label>
                            <input type="number" id="initial_balance" name="initial_balance" value="1000" step="0.01" min="0">
                        </div>
                        <button type="submit" name="add_user" class="btn">Add User</button>
                    </form>
                </div>
            </div>
            
            <!-- Update Wallet Modal -->
            <div id="update-wallet-modal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal('update-wallet-modal')">&times;</span>
                    <h2>Update Wallet Balance</h2>
                    <form method="post">
                        <input type="hidden" id="wallet_user_id" name="wallet_user_id">
                        <div>
                            <label for="username_display">Username</label>
                            <input type="text" id="username_display" readonly>
                        </div>
                        <div>
                            <label for="current_balance">Current Balance</label>
                            <input type="text" id="current_balance" readonly>
                        </div>
                        <div>
                            <label for="new_balance">New Balance</label>
                            <input type="number" id="new_balance" name="new_balance" step="0.01" min="0" required>
                        </div>
                        <button type="submit" name="update_wallet" class="btn">Update Balance</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Tab functionality
        function openTab(tabId) {
            // Hide all tab contents
            const tabContents = document.getElementsByClassName('tab-content');
            for (let i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove('active');
            }
            
            // Deactivate all tabs
            const tabs = document.getElementsByClassName('tab');
            for (let i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove('active');
            }
            
            // Show the selected tab content
            document.getElementById(tabId).classList.add('active');
            
            // Activate the clicked tab
            const clickedTab = event.currentTarget;
            clickedTab.classList.add('active');
        }
        
        // Check URL parameters for tab selection
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            
            if (tabParam) {
                const tabId = tabParam + '-tab';
                const tabElement = document.getElementById(tabId);
                
                if (tabElement) {
                    // Hide all tab contents
                    const tabContents = document.getElementsByClassName('tab-content');
                    for (let i = 0; i < tabContents.length; i++) {
                        tabContents[i].classList.remove('active');
                    }
                    
                    // Deactivate all tabs
                    const tabs = document.getElementsByClassName('tab');
                    for (let i = 0; i < tabs.length; i++) {
                        tabs[i].classList.remove('active');
                    }
                    
                    // Show the selected tab content
                    tabElement.classList.add('active');
                    
                    // Find and activate the corresponding tab button
                    const tabButtons = document.querySelectorAll('.tab');
                    for (let i = 0; i < tabButtons.length; i++) {
                        if (tabButtons[i].getAttribute('onclick').includes(tabId)) {
                            tabButtons[i].classList.add('active');
                            break;
                        }
                    }
                }
            }
        });
        
        // Modal functionality
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function openUpdateWalletModal(userId, username, balance) {
            document.getElementById('wallet_user_id').value = userId;
            document.getElementById('username_display').value = username;
            document.getElementById('current_balance').value = '$' + balance.toFixed(2);
            document.getElementById('new_balance').value = balance.toFixed(2);
            openModal('update-wallet-modal');
        }
        
        // Close modal when clicking outside the modal content
        window.onclick = function(event) {
            const modals = document.getElementsByClassName('modal');
            for (let i = 0; i < modals.length; i++) {
                if (event.target == modals[i]) {
                    modals[i].style.display = 'none';
                }
            }
        }
    </script>
</body>
</html> 