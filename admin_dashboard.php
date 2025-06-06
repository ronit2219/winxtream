<?php
require_once 'includes/auth_functions.php';
require_once 'includes/db_connect.php';

// Check if admin is logged in
if (!isLoggedIn() || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: /Home-Page/login.php');
    exit;
}

$admin_authenticated = true;

// Get dashboard data
$stats = [];
try {
    // Get total users
    $query = "SELECT COUNT(*) as total_users FROM users";
    $stmt = $pdo->query($query);
    $stats['total_users'] = $stmt->fetchColumn();
    
    // Get total balance
    $query = "SELECT SUM(balance) as total_balance FROM wallets";
    $stmt = $pdo->query($query);
    $stats['total_balance'] = $stmt->fetchColumn() ?: 0;
    
    // Get total wins
    $query = "SELECT SUM(profit) as total_wins FROM game_sessions WHERE result = 'win'";
    $stmt = $pdo->query($query);
    $stats['total_wins'] = $stmt->fetchColumn() ?: 0;
    
    // Get total losses
    $query = "SELECT ABS(SUM(profit)) as total_losses FROM game_sessions WHERE result = 'loss'";
    $stmt = $pdo->query($query);
    $stats['total_losses'] = $stmt->fetchColumn() ?: 0;
    
} catch (PDOException $e) {
    error_log("Error fetching dashboard stats: " . $e->getMessage());
}

// Determine current tab
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'users';

// Handle payment approval
if (isset($_POST['approve_payment']) && $admin_authenticated) {
    $request_id = $_POST['request_id'] ?? 0;
    $user_id = $_POST['user_id'] ?? 0;
    $amount = floatval($_POST['amount'] ?? 0);
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // 1. Check if request exists and is still pending
        $checkStmt = $pdo->prepare("SELECT status FROM payment_requests WHERE request_id = ? AND user_id = ?");
        $checkStmt->execute([$request_id, $user_id]);
        $status = $checkStmt->fetchColumn();
        
        if ($status !== 'pending') {
            throw new PDOException("This payment request has already been " . $status);
        }
        
        // 2. Update request status
        $updateStmt = $pdo->prepare("
            UPDATE payment_requests 
            SET status = 'approved', 
                updated_at = NOW()
            WHERE request_id = ?
        ");
        $updateStmt->execute([$request_id]);
        
        // 3. Add amount to user's wallet
        $walletStmt = $pdo->prepare("
            UPDATE wallets 
            SET balance = balance + ? 
            WHERE user_id = ?
        ");
        $walletStmt->execute([$amount, $user_id]);
        
        if ($walletStmt->rowCount() === 0) {
            throw new PDOException("Wallet not found");
        }
        
        // 4. Add transaction record
        $transStmt = $pdo->prepare("
            INSERT INTO transactions (wallet_id, amount, type, game) 
            SELECT wallet_id, ?, 'deposit', 'payment' 
            FROM wallets 
            WHERE user_id = ?
        ");
        $transStmt->execute([$amount, $user_id]);
        
        $pdo->commit();
        $payment_success = "Payment request of $" . number_format($amount, 2) . " approved successfully.";
        
        // Redirect to maintain the current tab
        header("Location: " . $_SERVER['PHP_SELF'] . "?tab=payment_requests&success=" . urlencode($payment_success));
        exit;
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $payment_error = 'Error approving payment: ' . $e->getMessage();
        error_log("Payment approval error: " . $e->getMessage());
    }
}

// Handle payment rejection
if (isset($_POST['reject_payment']) && $admin_authenticated) {
    $request_id = $_POST['request_id'] ?? 0;
    $user_id = $_POST['user_id'] ?? 0;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE payment_requests 
            SET status = 'rejected', 
                updated_at = NOW() 
            WHERE request_id = ? AND user_id = ?
        ");
        $stmt->execute([$request_id, $user_id]);
        
        $payment_success = 'Payment request rejected successfully.';
        
        // Redirect to maintain the current tab
        header("Location: " . $_SERVER['PHP_SELF'] . "?tab=payment_requests&success=" . urlencode($payment_success));
        exit;
        
    } catch (PDOException $e) {
        $payment_error = 'Error rejecting payment: ' . $e->getMessage();
        error_log("Payment rejection error: " . $e->getMessage());
    }
}

// Get withdrawal requests with usernames
try {
    $stmt = $pdo->prepare("
        SELECT 
            w.request_id, 
            w.user_id, 
            u.username, 
            w.amount, 
            w.upi_id,
            w.status, 
            w.processed_at, 
            w.created_at,
            w.utr_number
        FROM 
            withdrawal_requests w
        JOIN 
            users u ON w.user_id = u.user_id
        ORDER BY 
            w.created_at DESC
        LIMIT 50
    ");
    $stmt->execute();
    $withdrawal_requests = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching withdrawal requests: " . $e->getMessage());
    $withdrawal_requests = [];
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
            
            // Refresh withdrawal requests
            $stmt->execute();
            $withdrawal_requests = $stmt->fetchAll();
            
            // Redirect to maintain the current tab
            header("Location: " . $_SERVER['PHP_SELF'] . "?tab=withdrawal_requests&success=" . urlencode($withdrawal_success));
            exit;
            
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
        
        // Refresh withdrawal requests
        $stmt = $pdo->prepare("
            SELECT 
                w.request_id, 
                w.user_id, 
                u.username, 
                w.amount, 
                w.upi_id,
                w.status, 
                w.processed_at, 
                w.created_at,
                w.utr_number
            FROM 
                withdrawal_requests w
            JOIN 
                users u ON w.user_id = u.user_id
            ORDER BY 
                w.created_at DESC
            LIMIT 50
        ");
        $stmt->execute();
        $withdrawal_requests = $stmt->fetchAll();
        
        // Redirect to maintain the current tab
        header("Location: " . $_SERVER['PHP_SELF'] . "?tab=withdrawal_requests&success=" . urlencode($withdrawal_success));
        exit;
        
    } catch (PDOException $e) {
        $withdrawal_error = 'Error rejecting withdrawal: ' . $e->getMessage();
        error_log("Withdrawal rejection error: " . $e->getMessage());
    }
}

// Process success/error messages from redirects
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error_message = $_GET['error'];
}

// Get payment requests with usernames
try {
    $stmt = $pdo->prepare("
        SELECT 
            p.request_id, 
            p.user_id, 
            u.username, 
            p.amount, 
            p.utr_number,
            p.status, 
            p.created_at,
            p.updated_at
        FROM 
            payment_requests p
        JOIN 
            users u ON p.user_id = u.user_id
        ORDER BY 
            p.created_at DESC
        LIMIT 50
    ");
    $stmt->execute();
    $payment_requests = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching payment requests: " . $e->getMessage());
    $payment_requests = [];
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
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #1a1a1a;
            color: #fff;
        }
        h1, h2 {
            color: #8b5cf6;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #333;
            padding-bottom: 10px;
        }
        .stats-container {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-card {
            background-color: #222;
            padding: 20px;
            border-radius: 8px;
            flex: 1;
        }
        .stat-card h3 {
            margin-top: 0;
            color: #888;
        }
        .stat-card .value {
            font-size: 2em;
            font-weight: bold;
        }
        .purple { color: #8b5cf6; }
        .green { color: #4CAF50; }
        .red { color: #f44336; }
        
        .tab-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .tab-btn {
            padding: 10px 20px;
            background-color: #333;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .tab-btn.active {
            background-color: #8b5cf6;
        }
        .panel {
            background-color: #222;
            padding: 20px;
            border-radius: 8px;
        }
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
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
            background-color: #2a2a2a;
        }
        tr:hover {
            background-color: #2a2a2a;
        }
        .btn {
            padding: 8px 16px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-primary {
            background-color: #8b5cf6;
        }
        .btn-danger {
            background-color: #f44336;
        }
        .btn-info {
            background-color: #209cee;
        }
        .success-message {
            color: #4CAF50;
            padding: 10px;
            margin: 10px 0;
            background-color: rgba(76, 175, 80, 0.1);
            border-radius: 4px;
        }
        .error-message {
            color: #f44336;
            padding: 10px;
            margin: 10px 0;
            background-color: rgba(244, 67, 54, 0.1);
            border-radius: 4px;
        }
        .pending { color: #ffc107; }
        .approved { color: #4CAF50; }
        .rejected { color: #f44336; }
    </style>
</head>
<body>
    <div class="header">
        <h1>WinXtream Admin Dashboard</h1>
        <div>
            Welcome, admin <a href="/?logout=1" style="color: #8b5cf6; text-decoration: none; margin-left: 10px;">Logout</a>
        </div>
    </div>
    
    <div class="stats-container">
        <div class="stat-card">
            <h3>Total Users</h3>
            <div class="value purple"><?php echo $stats['total_users'] ?? 0; ?></div>
        </div>
        
        <div class="stat-card">
            <h3>Total Balance</h3>
            <div class="value purple">$<?php echo number_format($stats['total_balance'] ?? 0, 2); ?></div>
        </div>
        
        <div class="stat-card">
            <h3>Total Wins</h3>
            <div class="value green">$<?php echo number_format($stats['total_wins'] ?? 0, 2); ?></div>
        </div>
        
        <div class="stat-card">
            <h3>Total Losses</h3>
            <div class="value red">$<?php echo number_format($stats['total_losses'] ?? 0, 2); ?></div>
        </div>
    </div>
    
    <div class="tab-buttons">
        <a href="?tab=users" class="tab-btn <?php echo $current_tab === 'users' ? 'active' : ''; ?>">Users</a>
        <a href="?tab=wallet_management" class="tab-btn <?php echo $current_tab === 'wallet_management' ? 'active' : ''; ?>">Wallet Management</a>
        <a href="?tab=payment_requests" class="tab-btn <?php echo $current_tab === 'payment_requests' ? 'active' : ''; ?>">Payment Requests</a>
        <a href="?tab=withdrawal_requests" class="tab-btn <?php echo $current_tab === 'withdrawal_requests' ? 'active' : ''; ?>">Withdrawal Requests</a>
        <a href="?tab=transactions" class="tab-btn <?php echo $current_tab === 'transactions' ? 'active' : ''; ?>">Transactions</a>
        <a href="?tab=game_sessions" class="tab-btn <?php echo $current_tab === 'game_sessions' ? 'active' : ''; ?>">Game Sessions</a>
    </div>
    
    <?php if ($current_tab === 'users'): ?>
    <div class="panel">
        <div class="panel-header">
            <h2>User Management</h2>
            <button class="btn btn-primary">Add New User</button>
        </div>
        
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
                <?php
                $stmt = $pdo->query("
                    SELECT u.*, w.balance 
                    FROM users u 
                    LEFT JOIN wallets w ON u.user_id = w.user_id 
                    ORDER BY u.user_id
                ");
                while ($user = $stmt->fetch()) {
                    echo "<tr>";
                    echo "<td>{$user['user_id']}</td>";
                    echo "<td>" . htmlspecialchars($user['username']) . "</td>";
                    echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                    echo "<td>$" . number_format($user['balance'] ?? 0, 2) . "</td>";
                    echo "<td>{$user['created_at']}</td>";
                    echo "<td><button class='btn btn-danger'>Remove</button></td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <?php if ($current_tab === 'payment_requests'): ?>
    <div class="panel">
        <div class="panel-header">
            <h2>Payment Requests</h2>
            <a href="?tab=payment_requests" class="btn btn-info">Refresh</a>
        </div>
        
        <?php if (isset($payment_success) || (isset($success_message) && $current_tab === 'payment_requests')): ?>
            <div class="success-message"><?php echo $payment_success ?? $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($payment_error) || (isset($error_message) && $current_tab === 'payment_requests')): ?>
            <div class="error-message"><?php echo $payment_error ?? $error_message; ?></div>
        <?php endif; ?>
        
        <?php if (empty($payment_requests)): ?>
            <p>No payment requests found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Username</th>
                        <th>Amount</th>
                        <th>UTR Number</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payment_requests as $request): ?>
                        <tr>
                            <td><?php echo date('M d, Y H:i', strtotime($request['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($request['username']); ?></td>
                            <td>$<?php echo number_format($request['amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($request['utr_number']); ?></td>
                            <td class="<?php echo $request['status']; ?>">
                                <?php echo ucfirst($request['status']); ?>
                            </td>
                            <td>
                                <?php if ($request['status'] === 'pending'): ?>
                                    <form method="post" style="display: inline-block; margin-right: 5px;">
                                        <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $request['user_id']; ?>">
                                        <input type="hidden" name="amount" value="<?php echo $request['amount']; ?>">
                                        <button type="submit" name="approve_payment" class="btn">Approve</button>
                                    </form>
                                    <form method="post" style="display: inline-block;">
                                        <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $request['user_id']; ?>">
                                        <button type="submit" name="reject_payment" class="btn btn-danger" onclick="return confirm('Are you sure you want to reject this payment request?');">Reject</button>
                                    </form>
                                <?php else: ?>
                                    <?php if ($request['updated_at']): ?>
                                        Processed on <?php echo date('M d, Y H:i', strtotime($request['updated_at'])); ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <?php if ($current_tab === 'withdrawal_requests'): ?>
    <div class="panel">
        <div class="panel-header">
            <h2>Withdrawal Requests</h2>
            <a href="?tab=withdrawal_requests" class="btn btn-info">Refresh</a>
        </div>
        
        <?php if (isset($withdrawal_success) || (isset($success_message) && $current_tab === 'withdrawal_requests')): ?>
            <div class="success-message"><?php echo $withdrawal_success ?? $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($withdrawal_error) || (isset($error_message) && $current_tab === 'withdrawal_requests')): ?>
            <div class="error-message"><?php echo $withdrawal_error ?? $error_message; ?></div>
        <?php endif; ?>
        
        <?php if (empty($withdrawal_requests)): ?>
            <p>No withdrawal requests found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Username</th>
                        <th>Amount</th>
                        <th>UPI ID</th>
                        <th>Status</th>
                        <th>UTR Number</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($withdrawal_requests as $request): ?>
                        <tr>
                            <td><?php echo date('M d, Y H:i', strtotime($request['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($request['username']); ?></td>
                            <td>$<?php echo number_format($request['amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($request['upi_id']); ?></td>
                            <td class="<?php echo $request['status']; ?>">
                                <?php echo ucfirst($request['status']); ?>
                            </td>
                            <td><?php echo $request['utr_number'] ? htmlspecialchars($request['utr_number']) : '-'; ?></td>
                            <td>
                                <?php if ($request['status'] === 'pending'): ?>
                                    <form method="post" style="display: inline-block; margin-right: 5px;">
                                        <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $request['user_id']; ?>">
                                        <input type="hidden" name="amount" value="<?php echo $request['amount']; ?>">
                                        <input type="text" name="utr_number" placeholder="Enter UTR" required style="width: 100px;">
                                        <button type="submit" name="approve_withdrawal" class="btn">Approve</button>
                                    </form>
                                    <form method="post" style="display: inline-block;">
                                        <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $request['user_id']; ?>">
                                        <button type="submit" name="reject_withdrawal" class="btn btn-danger" onclick="return confirm('Are you sure you want to reject this withdrawal request?');">Reject</button>
                                    </form>
                                <?php else: ?>
                                    <?php if ($request['processed_at']): ?>
                                        Processed on <?php echo date('M d, Y H:i', strtotime($request['processed_at'])); ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <?php if ($current_tab === 'transactions'): ?>
    <div class="panel">
        <div class="panel-header">
            <h2>Transactions</h2>
            <a href="?tab=transactions" class="btn btn-info">Refresh</a>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Username</th>
                    <th>Amount</th>
                    <th>Type</th>
                    <th>Game</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->query("
                    SELECT t.*, u.username 
                    FROM transactions t 
                    JOIN wallets w ON t.wallet_id = w.wallet_id 
                    JOIN users u ON w.user_id = u.user_id 
                    ORDER BY t.created_at DESC 
                    LIMIT 100
                ");
                while ($transaction = $stmt->fetch()) {
                    $amountClass = $transaction['amount'] >= 0 ? 'approved' : 'rejected';
                    echo "<tr>";
                    echo "<td>" . date('M d, Y H:i', strtotime($transaction['created_at'])) . "</td>";
                    echo "<td>" . htmlspecialchars($transaction['username']) . "</td>";
                    echo "<td class='$amountClass'>$" . number_format($transaction['amount'], 2) . "</td>";
                    echo "<td>" . ucfirst($transaction['type']) . "</td>";
                    echo "<td>" . ucfirst($transaction['game']) . "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</body>
</html> 