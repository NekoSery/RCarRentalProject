<?php
// Database Configuration for Laragon
// Default Laragon MySQL credentials

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Default Laragon has empty password
define('DB_NAME', 'rcar_rental');

// Create database connection
function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
            
            $conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            die("Database connection error: " . $e->getMessage());
        }
    }
    
    return $conn;
}

// Helper function to close connection
function closeDBConnection() {
    $conn = getDBConnection();
    if ($conn) {
        $conn->close();
    }
}
?>
