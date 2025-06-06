<?php
// Enable error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Database Connection Check</h1>";

// Check db_connect.php
echo "<h2>Testing includes/db_connect.php</h2>";
try {
    require_once 'includes/db_connect.php';
    echo "<p>Connection successful!</p>";
    
    // Check database name
    $dbname = $pdo->query("SELECT DATABASE()")->fetchColumn();
    echo "<p>Connected to database: <strong>$dbname</strong></p>";
    
    // Check tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>Tables in database:</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // Check if withdrawal_requests table exists
    if (in_array('withdrawal_requests', $tables)) {
        echo "<p style='color:green;'>✓ withdrawal_requests table exists</p>";
        
        // Count records
        $count = $pdo->query("SELECT COUNT(*) FROM withdrawal_requests")->fetchColumn();
        echo "<p>Number of withdrawal requests: $count</p>";
        
        // Show table structure
        $columns = $pdo->query("SHOW COLUMNS FROM withdrawal_requests");
        echo "<p>Table structure:</p>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($column = $columns->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Create a test withdrawal request
        echo "<h3>Create a test withdrawal request</h3>";
        echo "<form method='post'>";
        echo "User ID: <input type='number' name='user_id' value='1'><br>";
        echo "Amount: <input type='number' name='amount' value='100' step='0.01'><br>";
        echo "UPI ID: <input type='text' name='upi_id' value='test@upi'><br>";
        echo "<input type='submit' name='create_request' value='Create Test Request'>";
        echo "</form>";
        
        if (isset($_POST['create_request'])) {
            $user_id = $_POST['user_id'];
            $amount = $_POST['amount'];
            $upi_id = $_POST['upi_id'];
            
            $stmt = $pdo->prepare("INSERT INTO withdrawal_requests (user_id, amount, upi_id, status) VALUES (?, ?, ?, 'pending')");
            $result = $stmt->execute([$user_id, $amount, $upi_id]);
            
            if ($result) {
                echo "<p style='color:green;'>Test withdrawal request created successfully!</p>";
            } else {
                echo "<p style='color:red;'>Failed to create test withdrawal request.</p>";
            }
        }
    } else {
        echo "<p style='color:red;'>✗ withdrawal_requests table does not exist!</p>";
        
        // Button to create the table
        echo "<form method='post'>";
        echo "<input type='submit' name='create_table' value='Create withdrawal_requests Table'>";
        echo "</form>";
        
        if (isset($_POST['create_table'])) {
            try {
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
                echo "<p style='color:green;'>withdrawal_requests table created successfully!</p>";
                echo "<p>Please refresh this page to confirm.</p>";
            } catch (PDOException $e) {
                echo "<p style='color:red;'>Error creating table: " . $e->getMessage() . "</p>";
            }
        }
    }
} catch (PDOException $e) {
    echo "<p style='color:red;'>Connection failed: " . $e->getMessage() . "</p>";
}

?> 