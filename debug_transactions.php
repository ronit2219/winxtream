<?php
// Debug script to check transactions
require_once 'includes/db_connection.php';

// Check if the transactions table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'transactions'");
    if ($stmt->rowCount() === 0) {
        die("Transactions table does not exist!");
    } else {
        echo "Transactions table exists.<br>";
    }
    
    // Get structure of transactions table
    $stmt = $pdo->query("DESCRIBE transactions");
    echo "<h3>Transactions table structure:</h3>";
    echo "<pre>";
    while ($row = $stmt->fetch()) {
        print_r($row);
    }
    echo "</pre>";
    
    // Count transactions
    $stmt = $pdo->query("SELECT COUNT(*) FROM transactions");
    $count = $stmt->fetchColumn();
    echo "Total transactions: {$count}<br>";
    
    // Fetch some transactions directly
    $stmt = $pdo->query("SELECT * FROM transactions ORDER BY created_at DESC LIMIT 10");
    $transactions = $stmt->fetchAll();
    
    echo "<h3>Latest 10 transactions:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Wallet ID</th><th>Amount</th><th>Type</th><th>Game</th><th>Created At</th></tr>";
    
    foreach ($transactions as $transaction) {
        echo "<tr>";
        echo "<td>" . $transaction['transaction_id'] . "</td>";
        echo "<td>" . $transaction['wallet_id'] . "</td>";
        echo "<td>" . $transaction['amount'] . "</td>";
        echo "<td>" . $transaction['type'] . "</td>";
        echo "<td>" . $transaction['game'] . "</td>";
        echo "<td>" . $transaction['created_at'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Check the wallets table for any issue
    echo "<h3>Wallets Check:</h3>";
    $stmt = $pdo->query("SELECT w.wallet_id, u.user_id, u.username FROM wallets w JOIN users u ON w.user_id = u.user_id");
    $wallets = $stmt->fetchAll();
    
    echo "<table border='1'>";
    echo "<tr><th>Wallet ID</th><th>User ID</th><th>Username</th></tr>";
    
    foreach ($wallets as $wallet) {
        echo "<tr>";
        echo "<td>" . $wallet['wallet_id'] . "</td>";
        echo "<td>" . $wallet['user_id'] . "</td>";
        echo "<td>" . $wallet['username'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Debug the getTransactions function
    echo "<h3>Testing getTransactions function:</h3>";
    
    function getTransactionsDebug($pdo, $limit = 10) {
        try {
            $stmt = $pdo->prepare("SELECT t.transaction_id, t.wallet_id, u.username, t.amount, t.type, t.game, t.created_at
                                  FROM transactions t
                                  LEFT JOIN wallets w ON t.wallet_id = w.wallet_id
                                  LEFT JOIN users u ON w.user_id = u.user_id
                                  ORDER BY t.created_at DESC
                                  LIMIT ?");
            $stmt->execute([$limit]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            return [];
        }
    }
    
    $debug_transactions = getTransactionsDebug($pdo);
    echo "Transactions from function: " . count($debug_transactions) . "<br>";
    
    if (count($debug_transactions) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Wallet ID</th><th>Username</th><th>Amount</th><th>Type</th><th>Game</th><th>Created At</th></tr>";
        
        foreach ($debug_transactions as $t) {
            echo "<tr>";
            echo "<td>" . $t['transaction_id'] . "</td>";
            echo "<td>" . $t['wallet_id'] . "</td>";
            echo "<td>" . ($t['username'] ?? 'NULL') . "</td>";
            echo "<td>" . $t['amount'] . "</td>";
            echo "<td>" . $t['type'] . "</td>";
            echo "<td>" . $t['game'] . "</td>";
            echo "<td>" . $t['created_at'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
} 