<?php
/**
 * Database Configuration and Connection
 * BloodBridge - Blood Donation & Emergency Assistance System
 * 
 * This file handles all database connections using MySQLi
 * Uses prepared statements to prevent SQL injection
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bloodbridge');
define('DB_PORT', 3306);

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connection Setup
try {
    // Create connection using MySQLi
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8mb4
    if (!$conn->set_charset('utf8mb4')) {
        throw new Exception("Error loading character set utf8mb4: " . $conn->error);
    }
    
    // Set timezone
    date_default_timezone_set('America/New_York');
    
} catch (Exception $e) {
    // Log error and show user-friendly message
    error_log('Database Connection Error: ' . $e->getMessage());
    die('Database connection failed. Please try again later.');
}

// Function to close database connection
function closeDB() {
    global $conn;
    if ($conn) {
        $conn->close();
    }
}

// Register shutdown function to close connection
register_shutdown_function('closeDB');

?>
