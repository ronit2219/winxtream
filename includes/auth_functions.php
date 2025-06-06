<?php
session_start();
require_once 'db_connect.php';

/**
 * Register a new user
 * 
 * @param string $username
 * @param string $email
 * @param string $password
 * @return array Response with status and message
 */
function registerUser($username, $email, $password) {
    global $pdo;
    
    try {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            if ($user['username'] === $username) {
                return ['status' => false, 'message' => 'Username already exists'];
            } else {
                return ['status' => false, 'message' => 'Email already exists'];
            }
        }
        
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
        $stmt = $pdo->prepare("INSERT INTO wallets (user_id, balance) VALUES (?, 1000.00)");
        $stmt->execute([$userId]);
        
        // Commit the transaction
        $pdo->commit();
        
        return ['status' => true, 'message' => 'User registered successfully', 'user_id' => $userId];
    } catch (PDOException $e) {
        // Rollback the transaction if something failed
        $pdo->rollback();
        return ['status' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
    }
}

/**
 * Authenticate a user
 * 
 * @param string $username
 * @param string $password
 * @return array Response with status, message and user data
 */
function loginUser($username, $password) {
    global $pdo;
    
    try {
        // Get user by username
        $stmt = $pdo->prepare("SELECT u.*, w.balance, w.wallet_id 
                               FROM users u 
                               JOIN wallets w ON u.user_id = w.user_id 
                               WHERE u.username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->rowCount() === 0) {
            return ['status' => false, 'message' => 'Username not found'];
        }
        
        $user = $stmt->fetch();
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            return ['status' => false, 'message' => 'Incorrect password'];
        }
        
        // Set session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['wallet_id'] = $user['wallet_id'];
        $_SESSION['balance'] = $user['balance'];
        
        return [
            'status' => true, 
            'message' => 'Login successful',
            'user' => [
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'balance' => $user['balance'],
                'wallet_id' => $user['wallet_id']
            ]
        ];
    } catch (PDOException $e) {
        return ['status' => false, 'message' => 'Login failed: ' . $e->getMessage()];
    }
}

/**
 * Check if user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Get current user data
 * 
 * @param bool $force_refresh Force refresh from database
 * @return array|null User data or null if not logged in
 */
function getCurrentUser($force_refresh = false) {
    if (!isLoggedIn()) {
        return null;
    }
    
    global $pdo, $conn;
    
    try {
        // Use mysqli connection first if available
        if (isset($conn) && $conn) {
            $stmt = $conn->prepare("
                SELECT u.user_id as id, u.username, u.email, w.balance 
                FROM users u 
                LEFT JOIN wallets w ON u.user_id = w.user_id 
                WHERE u.user_id = ?
            ");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                // Session exists but user not found in DB
                session_destroy();
                return null;
            }
            
            $user = $result->fetch_assoc();
            
            // Update session balance (might have changed)
            $_SESSION['balance'] = $user['balance'];
            
            $stmt->close();
            return $user;
        }
        // Fallback to PDO
        else if (isset($pdo)) {
            $stmt = $pdo->prepare("
                SELECT u.user_id as id, u.username, u.email, w.balance 
                FROM users u 
                LEFT JOIN wallets w ON u.user_id = w.user_id 
                WHERE u.user_id = ?
            ");
            $stmt->execute([$_SESSION['user_id']]);
            
            if ($stmt->rowCount() === 0) {
                // Session exists but user not found in DB
                session_destroy();
                return null;
            }
            
            $user = $stmt->fetch();
            
            // Update session balance (might have changed)
            $_SESSION['balance'] = $user['balance'];
            
            return $user;
        }
        else {
            error_log("No database connection available");
            return null;
        }
    } catch (Exception $e) {
        error_log("Error fetching user data: " . $e->getMessage());
        return null;
    }
}

/**
 * Log out the current user
 */
function logoutUser() {
    // Clear all session variables
    $_SESSION = array();
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
}

/**
 * Get user's wallet balance
 * 
 * @param int $userId
 * @return float|null Balance or null if error
 */
function getUserBalance($userId = null) {
    if ($userId === null && !isLoggedIn()) {
        return null;
    }
    
    $userId = $userId ?? $_SESSION['user_id'];
    
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        if ($stmt->rowCount() === 0) {
            return null;
        }
        
        $wallet = $stmt->fetch();
        return (float)$wallet['balance'];
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Update user's wallet balance
 * 
 * @param float $amount Amount to add (positive) or subtract (negative)
 * @param string $type Type of transaction (deposit, withdrawal, win, loss)
 * @param string $game Game name (optional)
 * @return bool True if successful, false otherwise
 */
function updateUserBalance($amount, $type, $game = null) {
    if (!isLoggedIn()) {
        return false;
    }
    
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Update wallet balance
        $stmt = $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ?");
        $stmt->execute([$amount, $_SESSION['user_id']]);
        
        // Record transaction
        $stmt = $pdo->prepare("INSERT INTO transactions (wallet_id, amount, type, game) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_SESSION['wallet_id'], $amount, $type, $game]);
        
        // Get updated balance
        $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $wallet = $stmt->fetch();
        
        // Update session balance
        $_SESSION['balance'] = $wallet['balance'];
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollback();
        return false;
    }
}

/**
 * Record a game session
 * 
 * @param string $game Game name
 * @param float $betAmount Bet amount
 * @param float $multiplier Multiplier (if applicable)
 * @param float $profit Profit amount (can be negative for losses)
 * @param string $result 'win' or 'loss'
 * @return bool True if successful, false otherwise
 */
function recordGameSession($game, $betAmount, $multiplier, $profit, $result) {
    if (!isLoggedIn()) {
        return false;
    }
    
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO game_sessions (user_id, game, bet_amount, multiplier, profit, result) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            $game,
            $betAmount,
            $multiplier,
            $profit,
            $result
        ]);
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
} 