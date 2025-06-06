<?php
// Enable error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection settings
$host = 'localhost';
$dbname = 'winxtream_db';
$username = 'root'; // Default XAMPP username
$password = ''; // Default XAMPP password is empty

// Create connection
try {
    // Check if the database exists first
    $pdo_check = new PDO("mysql:host=$host", $username, $password);
    $pdo_check->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if database exists, create if not
    $stmt = $pdo_check->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'");
    $db_exists = (bool)$stmt->fetchColumn();
    
    if (!$db_exists) {
        // Log the issue
        error_log("Database $dbname does not exist. Please import the winxtream_db.sql file.");
        die("Database not found. Please make sure to import the winxtream_db.sql file into your MySQL server.");
    }
    
    // Connect to the specific database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Check if the users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() === 0) {
        error_log("The 'users' table does not exist in $dbname. Please import the winxtream_db.sql file correctly.");
        die("Database tables not found. Please make sure to import the winxtream_db.sql file correctly.");
    }

} catch(PDOException $e) {
    // Log the error
    error_log("Database connection failed: " . $e->getMessage());
    
    // If connection fails
    die("Connection failed: " . $e->getMessage() . ". Please make sure your MySQL server is running and the database is properly set up.");
} 