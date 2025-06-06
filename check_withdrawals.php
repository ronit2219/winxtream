<?php
// Simple diagnostic script to check withdrawal requests

// Include database connection
require_once 'includes/db_connect.php';

echo "<h1>Withdrawal Requests Diagnostic</h1>";

// Check if withdrawal_requests table exists
try {
    $checkTable = $pdo->query("SHOW TABLES LIKE 'withdrawal_requests'");
    if ($checkTable->rowCount() == 0) {
        echo "<p style='color:red'>Error: withdrawal_requests table doesn't exist!</p>";
        exit;
    } else {
        echo "<p style='color:green'>✓ withdrawal_requests table exists</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red'>Database error: " . $e->getMessage() . "</p>";
    exit;
}

// Check withdrawal requests table structure
try {
    $columns = $pdo->query("SHOW COLUMNS FROM withdrawal_requests");
    echo "<h2>Table Structure:</h2>";
    echo "<ul>";
    while ($column = $columns->fetch(PDO::FETCH_ASSOC)) {
        echo "<li>" . $column['Field'] . " - " . $column['Type'] . " " . ($column['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . "</li>";
    }
    echo "</ul>";
} catch (PDOException $e) {
    echo "<p style='color:red'>Error getting table structure: " . $e->getMessage() . "</p>";
}

// Count total withdrawal requests
try {
    $count = $pdo->query("SELECT COUNT(*) FROM withdrawal_requests")->fetchColumn();
    echo "<h2>Total Withdrawal Requests: $count</h2>";
} catch (PDOException $e) {
    echo "<p style='color:red'>Error counting requests: " . $e->getMessage() . "</p>";
}

// Show all withdrawal requests
try {
    $stmt = $pdo->query("SELECT * FROM withdrawal_requests ORDER BY created_at DESC");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($requests)) {
        echo "<p style='color:orange'>No withdrawal requests found in the database.</p>";
    } else {
        echo "<h2>All Withdrawal Requests:</h2>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr>
                <th>ID</th>
                <th>User ID</th>
                <th>Amount</th>
                <th>UPI ID</th>
                <th>Status</th>
                <th>UTR Number</th>
                <th>Created</th>
                <th>Processed</th>
              </tr>";
              
        foreach ($requests as $request) {
            echo "<tr>";
            echo "<td>" . $request['request_id'] . "</td>";
            echo "<td>" . $request['user_id'] . "</td>";
            echo "<td>$" . number_format($request['amount'], 2) . "</td>";
            echo "<td>" . htmlspecialchars($request['upi_id']) . "</td>";
            echo "<td>" . $request['status'] . "</td>";
            echo "<td>" . ($request['utr_number'] ? htmlspecialchars($request['utr_number']) : '-') . "</td>";
            echo "<td>" . $request['created_at'] . "</td>";
            echo "<td>" . ($request['processed_at'] ? $request['processed_at'] : 'Not processed') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red'>Error fetching requests: " . $e->getMessage() . "</p>";
}

// Check database connection info
echo "<h2>Database Connection Info:</h2>";
try {
    $dbname = $pdo->query("SELECT DATABASE()")->fetchColumn();
    echo "<p>Current database: <strong>$dbname</strong></p>";
    
    // Check if admin might be using a different database
    $adminDbPath = 'admin/includes/db_connect.php';
    if (file_exists($adminDbPath)) {
        echo "<p style='color:orange'>Warning: Admin might be using a different database connection ($adminDbPath)</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red'>Error getting database info: " . $e->getMessage() . "</p>";
}

// Show last 3 withdrawal attempts from log (if it exists)
$logFile = 'logs/withdrawal.log';
if (file_exists($logFile)) {
    echo "<h2>Recent Withdrawal Logs:</h2>";
    echo "<pre>" . htmlspecialchars(shell_exec("tail -n 20 $logFile")) . "</pre>";
} else {
    echo "<p>No withdrawal log file found.</p>";
}
?> 