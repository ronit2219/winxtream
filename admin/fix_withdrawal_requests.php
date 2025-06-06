<?php
// Enable error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start a session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connect to database
require_once '../includes/db_connect.php';

// Check if user is admin
$is_admin = isset($_SESSION['admin_id']) || (isset($_SESSION['is_admin']) && $_SESSION['is_admin']);

if (!$is_admin) {
    echo "<div style='color:red; padding:20px; background:#333; font-family:sans-serif;'>";
    echo "<h1>Access Denied</h1>";
    echo "<p>You must be logged in as an admin to use this tool.</p>";
    echo "<p><a href='admin_dashboard.php' style='color:#fff;'>Go to Admin Login</a></p>";
    echo "</div>";
    exit;
}

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Withdrawal Requests Diagnostic</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: #121212;
            color: #e0e0e0;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: #1e1e1e;
            padding: 20px;
            border-radius: 8px;
        }
        h1, h2 {
            color: #7c4dff;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #333;
        }
        th {
            background: #252525;
        }
        .success {
            color: #4CAF50;
            background: rgba(76, 175, 80, 0.1);
            padding: 10px;
            border-radius: 4px;
        }
        .error {
            color: #F44336;
            background: rgba(244, 67, 54, 0.1);
            padding: 10px;
            border-radius: 4px;
        }
        .info {
            color: #2196F3;
            background: rgba(33, 150, 243, 0.1);
            padding: 10px;
            border-radius: 4px;
        }
        button, input[type='submit'] {
            background: #7c4dff;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px 0;
        }
        button:hover, input[type='submit']:hover {
            background: #6a3ecf;
        }
        .action-btn {
            display: inline-block;
            margin: 5px;
        }
    </style>
</head>
<body>
    <div class='container'>";

echo "<h1>Withdrawal Requests Diagnostic and Repair Tool</h1>";
echo "<p>This tool helps diagnose and fix issues with withdrawal requests.</p>";

// Handle actions
$action = $_POST['action'] ?? '';
$message = '';

if ($action == 'clear_session') {
    unset($_SESSION['withdrawal_requests']);
    $message = "<div class='success'>Session data for withdrawal requests has been cleared.</div>";
} 
elseif ($action == 'check_table') {
    try {
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'withdrawal_requests'");
        if ($tableCheck->rowCount() == 0) {
            $message = "<div class='error'>The withdrawal_requests table does not exist in the database!</div>";
            
            // Show create table button
            $message .= "<form method='post'>
                <input type='hidden' name='action' value='create_table'>
                <input type='submit' value='Create Table'>
            </form>";
        } else {
            // Check column structure
            $columns = $pdo->query("SHOW COLUMNS FROM withdrawal_requests");
            $columnList = [];
            while ($col = $columns->fetch(PDO::FETCH_ASSOC)) {
                $columnList[] = $col['Field'];
            }
            
            $requiredColumns = ['request_id', 'user_id', 'amount', 'upi_id', 'status', 'utr_number', 'processed_at', 'created_at', 'updated_at', 'admin_note'];
            $missingColumns = array_diff($requiredColumns, $columnList);
            
            if (!empty($missingColumns)) {
                $message = "<div class='error'>Table exists but is missing these columns: " . implode(', ', $missingColumns) . "</div>";
            } else {
                $count = $pdo->query("SELECT COUNT(*) FROM withdrawal_requests")->fetchColumn();
                $message = "<div class='success'>Table exists with correct structure! Contains $count records.</div>";
            }
        }
    } catch (PDOException $e) {
        $message = "<div class='error'>Database error: " . $e->getMessage() . "</div>";
    }
} 
elseif ($action == 'create_table') {
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
        $message = "<div class='success'>withdrawal_requests table created successfully!</div>";
    } catch (PDOException $e) {
        $message = "<div class='error'>Error creating table: " . $e->getMessage() . "</div>";
    }
} 
elseif ($action == 'create_test_request') {
    try {
        // First check if we have any users
        $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        
        if ($userCount == 0) {
            $message = "<div class='error'>No users found. Please create a user first.</div>";
        } else {
            // Get the first user ID
            $userId = $pdo->query("SELECT user_id FROM users LIMIT 1")->fetchColumn();
            
            // Insert a test withdrawal request
            $stmt = $pdo->prepare("INSERT INTO withdrawal_requests (user_id, amount, upi_id, status) VALUES (?, ?, ?, ?)");
            $result = $stmt->execute([$userId, 100.00, 'test@upi', 'pending']);
            
            if ($result) {
                // Reset session data for withdrawal requests
                unset($_SESSION['withdrawal_requests']);
                
                $message = "<div class='success'>Test withdrawal request created successfully!</div>";
            } else {
                $message = "<div class='error'>Failed to create test withdrawal request.</div>";
            }
        }
    } catch (PDOException $e) {
        $message = "<div class='error'>Error creating test request: " . $e->getMessage() . "</div>";
    }
} 
elseif ($action == 'show_requests') {
    try {
        // Direct query to get all withdrawal requests
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
        
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($requests)) {
            $message = "<div class='info'>No withdrawal requests found in the database.</div>";
        } else {
            $message = "<div class='success'>Found " . count($requests) . " withdrawal requests.</div>";
            $message .= "<table>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Amount</th>
                    <th>UPI ID</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>";
            
            foreach ($requests as $req) {
                $message .= "<tr>
                    <td>{$req['request_id']}</td>
                    <td>{$req['username']} (ID: {$req['user_id']})</td>
                    <td>\${$req['amount']}</td>
                    <td>{$req['upi_id']}</td>
                    <td>{$req['status']}</td>
                    <td>{$req['created_at']}</td>
                </tr>";
            }
            
            $message .= "</table>";
        }
    } catch (PDOException $e) {
        $message = "<div class='error'>Error fetching withdrawal requests: " . $e->getMessage() . "</div>";
    }
}

// Display any messages
echo $message;

// Display diagnostic options
echo "<h2>Available Actions</h2>";
echo "<form method='post' style='margin-bottom: 10px;'>
    <input type='hidden' name='action' value='check_table'>
    <input type='submit' value='Check Withdrawal Requests Table' class='action-btn'>
</form>";

echo "<form method='post' style='margin-bottom: 10px;'>
    <input type='hidden' name='action' value='show_requests'>
    <input type='submit' value='Show All Withdrawal Requests' class='action-btn'>
</form>";

echo "<form method='post' style='margin-bottom: 10px;'>
    <input type='hidden' name='action' value='create_test_request'>
    <input type='submit' value='Create Test Request' class='action-btn'>
</form>";

echo "<form method='post' style='margin-bottom: 10px;'>
    <input type='hidden' name='action' value='clear_session'>
    <input type='submit' value='Clear Session Data' class='action-btn'>
</form>";

// Links back to admin
echo "<div style='margin-top: 20px; padding-top: 20px; border-top: 1px solid #333;'>
    <a href='admin_dashboard.php' style='color: #7c4dff; text-decoration: none;'>Back to Admin Dashboard</a>
    &nbsp;|&nbsp;
    <a href='admin_dashboard.php?tab=withdrawal_requests' style='color: #7c4dff; text-decoration: none;'>Go to Withdrawal Requests Tab</a>
</div>";

echo "</div></body></html>";
?> 