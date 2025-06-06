<?php
session_start();
require_once '../includes/db_connection.php';

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
        $stmt = $pdo->prepare("SELECT t.transaction_id, t.wallet_id, u.username, t.amount, t.type, t.game, t.created_at
                               FROM transactions t
                               JOIN wallets w ON t.wallet_id = w.wallet_id
                               JOIN users u ON w.user_id = u.user_id
                               ORDER BY t.created_at DESC
                               LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
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

// Data for dashboard if authenticated
if ($admin_authenticated) {
    $users = getUsers($pdo);
    $transactions = getTransactions($pdo);
    $game_sessions = getGameSessions($pdo);
    
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
        .positive {
            color: #4caf50;
        }
        .negative {
            color: #f44336;
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
                <div class="tab active" data-tab="users">Users</div>
                <div class="tab" data-tab="transactions">Transactions</div>
                <div class="tab" data-tab="game-sessions">Game Sessions</div>
            </div>
            
            <!-- Users Tab -->
            <div class="tab-content active" id="users">
                <div class="panel">
                    <div class="panel-header">
                        <h2>User Management</h2>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Balance</th>
                                <th>Created At</th>
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
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Transactions Tab -->
            <div class="tab-content" id="transactions">
                <div class="panel">
                    <div class="panel-header">
                        <h2>Recent Transactions</h2>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Amount</th>
                                <th>Type</th>
                                <th>Game</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><?php echo $transaction['transaction_id']; ?></td>
                                    <td><?php echo htmlspecialchars($transaction['username']); ?></td>
                                    <td class="<?php echo $transaction['amount'] >= 0 ? 'positive' : 'negative'; ?>">
                                        $<?php echo number_format($transaction['amount'], 2); ?>
                                    </td>
                                    <td><?php echo ucfirst($transaction['type']); ?></td>
                                    <td><?php echo $transaction['game'] ? ucfirst($transaction['game']) : '-'; ?></td>
                                    <td><?php echo $transaction['created_at']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Game Sessions Tab -->
            <div class="tab-content" id="game-sessions">
                <div class="panel">
                    <div class="panel-header">
                        <h2>Recent Game Sessions</h2>
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
                                    <td><?php echo ucfirst($session['game']); ?></td>
                                    <td>$<?php echo number_format($session['bet_amount'], 2); ?></td>
                                    <td><?php echo $session['multiplier'] ? number_format($session['multiplier'], 2) . 'x' : '-'; ?></td>
                                    <td class="<?php echo $session['profit'] >= 0 ? 'positive' : 'negative'; ?>">
                                        $<?php echo number_format($session['profit'], 2); ?>
                                    </td>
                                    <td><?php echo ucfirst($session['result']); ?></td>
                                    <td><?php echo $session['created_at']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <script>
                // Tab switching
                const tabs = document.querySelectorAll('.tab');
                
                tabs.forEach(tab => {
                    tab.addEventListener('click', () => {
                        // Remove active class from all tabs
                        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                        
                        // Add active class to clicked tab
                        tab.classList.add('active');
                        
                        // Show corresponding content
                        const tabId = tab.getAttribute('data-tab');
                        document.getElementById(tabId).classList.add('active');
                    });
                });
            </script>
        <?php endif; ?>
    </div>
</body>
</html> 