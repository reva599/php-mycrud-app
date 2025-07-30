<?php
/**
 * Simple Database Configuration
 * Basic working configuration for XAMPP
 */

// Database configuration
$host = 'localhost';
$username = 'root';
$password = ''; // Try empty first, then 'root' if needed
$database = 'blog';

// Create connection
try {
    $conn = new mysqli($host, $username, $password, $database);
    
    // Check connection
    if ($conn->connect_error) {
        // Try with password 'root'
        $conn = new mysqli($host, $username, 'root', $database);
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
    }
    
    // Set charset
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    die("
    <div style='font-family: Arial, sans-serif; margin: 20px; padding: 20px; border: 1px solid #dc3545; background: #f8d7da; color: #721c24; border-radius: 5px;'>
        <h3>🔧 Database Connection Failed</h3>
        <p><strong>Error:</strong> " . $e->getMessage() . "</p>
        
        <h4>Quick Fix Steps:</h4>
        <ol>
            <li><strong>Check XAMPP:</strong> Make sure MySQL service is running in XAMPP Control Panel</li>
            <li><strong>Create Database:</strong>
                <ul>
                    <li>Go to <a href='http://localhost/phpmyadmin' target='_blank'>phpMyAdmin</a></li>
                    <li>Create database named 'blog'</li>
                    <li>Run the SQL commands provided above</li>
                </ul>
            </li>
            <li><strong>Check Password:</strong> Try these in phpMyAdmin:
                <ul>
                    <li>Username: root, Password: (empty)</li>
                    <li>Username: root, Password: root</li>
                </ul>
            </li>
        </ol>
    </div>
    ");
}

// Helper functions for database operations
function getSingleResult($conn, $sql, $types = '', $params = []) {
    if (empty($params)) {
        $result = $conn->query($sql);
        return $result ? $result->fetch_assoc() : null;
    }
    
    $stmt = $conn->prepare($sql);
    if ($stmt && !empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if ($stmt && $stmt->execute()) {
        $result = $stmt->get_result();
        return $result ? $result->fetch_assoc() : null;
    }
    
    return null;
}

function getMultipleResults($conn, $sql, $types = '', $params = []) {
    if (empty($params)) {
        $result = $conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    $stmt = $conn->prepare($sql);
    if ($stmt && !empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if ($stmt && $stmt->execute()) {
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    return [];
}

function executeQuery($conn, $sql, $types = '', $params = []) {
    $stmt = $conn->prepare($sql);
    if ($stmt && !empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if ($stmt && $stmt->execute()) {
        return $stmt;
    }
    
    return false;
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Basic helper functions
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: auth/login.php');
        exit();
    }
}

function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        header('Location: dashboard.php');
        exit();
    }
}

// Success message
if (DEVELOPMENT_MODE ?? true) {
    echo "<!-- Database connected successfully -->";
}
?>
