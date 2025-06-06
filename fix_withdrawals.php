<?php
// Enable error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Fix Withdrawal Requests</h1>";

// Connect to the database
require_once 'includes/db_connect.php';

try {
    // Check if withdrawal_requests table exists
    $checkTable = $pdo->query("SHOW TABLES LIKE 'withdrawal_requests'");
    
    if ($checkTable->rowCount() == 0) {
        echo "<p>The withdrawal_requests table does not exist. Creating it now...</p>";
        
        // Create the table
        $sql = "CREATE TABLE withdrawal_requests (
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
        echo "<p style='color:green;'>Table created successfully!</p>";
    } else {
        echo "<p>The withdrawal_requests table already exists.</p>";
    }
    
    // Count existing withdrawal requests
    $count = $pdo->query("SELECT COUNT(*) FROM withdrawal_requests")->fetchColumn();
    echo "<p>Found $count existing withdrawal requests.</p>";
    
    // Create a test withdrawal request if none exist
    if ($count == 0) {
        echo "<p>No withdrawal requests found. Creating a test request...</p>";
        
        // First make sure we have at least one user
        $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        
        if ($userCount > 0) {
            // Get the first user ID
            $userId = $pdo->query("SELECT user_id FROM users LIMIT 1")->fetchColumn();
            
            // Insert a test request
            $stmt = $pdo->prepare("INSERT INTO withdrawal_requests (user_id, amount, upi_id, status) VALUES (?, ?, ?, ?)");
            $result = $stmt->execute([$userId, 100.00, 'test@upi', 'pending']);
            
            if ($result) {
                echo "<p style='color:green;'>Test withdrawal request created successfully!</p>";
            } else {
                echo "<p style='color:red;'>Failed to create test withdrawal request.</p>";
            }
        } else {
            echo "<p style='color:red;'>No users found in the database. Please create a user first.</p>";
        }
    }
    
    // Display all withdrawal requests
    $requests = $pdo->query("SELECT wr.*, u.username 
                           FROM withdrawal_requests wr 
                           LEFT JOIN users u ON wr.user_id = u.user_id 
                           ORDER BY wr.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($requests) > 0) {
        echo "<h2>Current Withdrawal Requests</h2>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>User</th><th>Amount</th><th>UPI ID</th><th>Status</th><th>Created</th></tr>";
        
        foreach ($requests as $req) {
            echo "<tr>";
            echo "<td>{$req['request_id']}</td>";
            echo "<td>{$req['username']} (ID: {$req['user_id']})</td>";
            echo "<td>\${$req['amount']}</td>";
            echo "<td>{$req['upi_id']}</td>";
            echo "<td>{$req['status']}</td>";
            echo "<td>{$req['created_at']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    echo "<p><a href='admin/admin_dashboard.php?tab=withdrawal_requests'>Go to Admin Dashboard</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>Database error: " . $e->getMessage() . "</p>";
}
?> 