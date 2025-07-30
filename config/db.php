<?php
/**
 * Database Configuration File
 * PHP CRUD Blog Application - ApexPlanet Internship
 * 
 * This file handles the database connection using MySQLi
 * with proper error handling and security practices.
 */

// Database configuration constants
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'blog');
define('DB_PORT', 3306);

// Create connection function
function getDatabaseConnection() {
    try {
        // Create MySQLi connection
        $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);
        
        // Check connection
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        // Set charset to utf8mb4 for full UTF-8 support
        if (!$conn->set_charset("utf8mb4")) {
            throw new Exception("Error setting charset: " . $conn->error);
        }
        
        return $conn;
        
    } catch (Exception $e) {
        // Log error (in production, log to file instead of displaying)
        error_log("Database connection error: " . $e->getMessage());
        
        // Display user-friendly error message
        die("Database connection failed. Please try again later.");
    }
}

// Global connection variable
$conn = getDatabaseConnection();

// Function to close database connection
function closeDatabaseConnection($connection) {
    if ($connection && !$connection->connect_error) {
        $connection->close();
    }
}

// Function to test database connection
function testDatabaseConnection() {
    try {
        $conn = getDatabaseConnection();
        $result = $conn->query("SELECT 1");
        
        if ($result) {
            closeDatabaseConnection($conn);
            return true;
        }
        
        return false;
        
    } catch (Exception $e) {
        return false;
    }
}

// Function to execute prepared statements safely
function executeQuery($conn, $sql, $types = "", $params = []) {
    try {
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        if (!empty($params) && !empty($types)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        return $stmt;
        
    } catch (Exception $e) {
        error_log("Query execution error: " . $e->getMessage());
        throw $e;
    }
}

// Function to get single result
function getSingleResult($conn, $sql, $types = "", $params = []) {
    try {
        $stmt = executeQuery($conn, $sql, $types, $params);
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row;
        
    } catch (Exception $e) {
        error_log("Get single result error: " . $e->getMessage());
        return false;
    }
}

// Function to get multiple results
function getMultipleResults($conn, $sql, $types = "", $params = []) {
    try {
        $stmt = executeQuery($conn, $sql, $types, $params);
        $result = $stmt->get_result();
        $rows = [];
        
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        
        $stmt->close();
        return $rows;
        
    } catch (Exception $e) {
        error_log("Get multiple results error: " . $e->getMessage());
        return [];
    }
}

// Enable error reporting for development (disable in production)
if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
}

?>
