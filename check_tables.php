<?php
// Include database connection
require_once 'includes/db_connect.php';

// Check for withdrawal_requests table
try {
    // Get list of all tables
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in database: " . implode(", ", $tables) . "\n\n";
    
    // Check if withdrawal_requests table exists
    $withdrawal_table_exists = in_array('withdrawal_requests', $tables);
    echo "Withdrawal requests table exists: " . ($withdrawal_table_exists ? "Yes" : "No") . "\n";
    
    // If it exists, count records
    if ($withdrawal_table_exists) {
        $count = $pdo->query('SELECT COUNT(*) FROM withdrawal_requests')->fetchColumn();
        echo "Total withdrawal requests: " . $count . "\n";
        
        // Show table structure
        $columns = $pdo->query('DESCRIBE withdrawal_requests')->fetchAll(PDO::FETCH_ASSOC);
        echo "\nWithdrawal requests table structure:\n";
        foreach ($columns as $column) {
            echo $column['Field'] . " - " . $column['Type'] . " - " . ($column['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
        }
    }
    
    // Check payment_requests table
    $payment_table_exists = in_array('payment_requests', $tables);
    echo "\nPayment requests table exists: " . ($payment_table_exists ? "Yes" : "No") . "\n";
    
    if ($payment_table_exists) {
        $count = $pdo->query('SELECT COUNT(*) FROM payment_requests')->fetchColumn();
        echo "Total payment requests: " . $count . "\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
} 