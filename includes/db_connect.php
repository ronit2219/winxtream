<?php
// Enable error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection settings for InfinityFree hosting
// Replace these with your actual InfinityFree database credentials
$host = 'localhost';
$dbname = 'winxtream_db';
$username = 'root'; // Default XAMPP username
$password = ''; // Default XAMPP password is empty

// Create connection using mysqli
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // Log the error
    error_log("Database connection failed: " . $conn->connect_error);
    
    // If connection fails
    die("Connection failed: " . $conn->connect_error . ". Please make sure your MySQL server is running and the database is properly set up.");
}

// Make the connection available globally
global $conn;

// Also create PDO connection for backward compatibility with existing code
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // Just log the error but don't die since we're primarily using mysqli
    error_log("PDO connection failed: " . $e->getMessage());
}
?> 