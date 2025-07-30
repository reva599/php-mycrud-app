<?php
/**
 * Final Project - Integrated Database Configuration
 * Comprehensive database setup with security features
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Change if your MySQL has a password
define('DB_NAME', 'final_blog_app');

// Security configuration
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes
define('SESSION_TIMEOUT', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 8);
define('CSRF_TOKEN_LENGTH', 32);

// Application configuration
define('POSTS_PER_PAGE', 6);
define('SEARCH_MIN_LENGTH', 3);
define('MAX_UPLOAD_SIZE', 5242880); // 5MB

try {
    // Create database connection
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
} catch (PDOException $e) {
    // Try to create database if it doesn't exist
    try {
        $pdo_temp = new PDO(
            "mysql:host=" . DB_HOST . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        $pdo_temp->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Now connect to the created database
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        
    } catch (PDOException $e2) {
        die("Database connection failed: " . $e2->getMessage() . 
            "<br><br>Please ensure MySQL is running and check your database credentials in config/database.php");
    }
}

// Database helper functions
function executeQuery($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetch() : null;
}

function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

function getLastInsertId() {
    global $pdo;
    return $pdo->lastInsertId();
}

// Session management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    
    // Session security
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > SESSION_TIMEOUT) {
        session_destroy();
        session_start();
    }
    
    // Regenerate session ID periodically
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } else if (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// CSRF Protection
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Input sanitization
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePassword($password) {
    return strlen($password) >= PASSWORD_MIN_LENGTH;
}

// Flash messages
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'message' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type'] ?? 'info'
        ];
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return $message;
    }
    return null;
}

// Authentication helpers
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    return fetchOne(
        "SELECT id, username, email, role, first_name, last_name, created_at FROM users WHERE id = ?",
        [$_SESSION['user_id']]
    );
}

function hasRole($required_role) {
    $user = getCurrentUser();
    if (!$user) return false;
    
    $roles = ['subscriber' => 1, 'author' => 2, 'editor' => 3, 'admin' => 4];
    $user_level = $roles[$user['role']] ?? 0;
    $required_level = $roles[$required_role] ?? 0;
    
    return $user_level >= $required_level;
}

function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: /final-project/auth/login.php');
        exit();
    }
}

function requireRole($role) {
    requireAuth();
    if (!hasRole($role)) {
        setFlashMessage('Access denied. Insufficient permissions.', 'error');
        header('Location: /final-project/index.php');
        exit();
    }
}

// Rate limiting for login attempts
function checkLoginAttempts($username) {
    $attempts = fetchOne(
        "SELECT attempts, last_attempt FROM login_attempts WHERE username = ? AND last_attempt > DATE_SUB(NOW(), INTERVAL ? SECOND)",
        [$username, LOGIN_LOCKOUT_TIME]
    );
    
    return $attempts ? $attempts['attempts'] : 0;
}

function recordLoginAttempt($username, $success = false) {
    if ($success) {
        executeQuery("DELETE FROM login_attempts WHERE username = ?", [$username]);
    } else {
        $existing = fetchOne("SELECT id FROM login_attempts WHERE username = ?", [$username]);
        
        if ($existing) {
            executeQuery(
                "UPDATE login_attempts SET attempts = attempts + 1, last_attempt = NOW() WHERE username = ?",
                [$username]
            );
        } else {
            executeQuery(
                "INSERT INTO login_attempts (username, attempts, last_attempt) VALUES (?, 1, NOW())",
                [$username]
            );
        }
    }
}

// Audit logging
function logActivity($user_id, $action, $details = '') {
    executeQuery(
        "INSERT INTO audit_log (user_id, action, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, NOW())",
        [$user_id, $action, $details, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']
    );
}

// Error handling
function handleError($message, $redirect = null) {
    error_log($message);
    setFlashMessage('An error occurred. Please try again.', 'error');
    
    if ($redirect) {
        header("Location: $redirect");
        exit();
    }
}

// Initialize database tables if they don't exist
function initializeDatabase() {
    global $pdo;
    
    // Check if tables exist
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        // Create tables
        $sql = file_get_contents(__DIR__ . '/../database/schema.sql');
        if ($sql) {
            $pdo->exec($sql);
        }
    }
}

// Initialize database on first load
initializeDatabase();
?>
