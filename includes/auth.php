<?php
/**
 * Authentication Helper Functions
 * PHP CRUD Blog Application - ApexPlanet Internship
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Require authentication - redirect to login if not logged in
 */
function requireAuth() {
    if (!isLoggedIn()) {
        setFlashMessage('Please log in to access this page.', 'warning');
        header('Location: ' . getBasePath() . 'auth/login.php');
        exit();
    }
}

/**
 * Redirect if already logged in
 */
function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        header('Location: ' . getBasePath() . 'dashboard.php');
        exit();
    }
}

/**
 * Get current user information
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    require_once __DIR__ . '/../config/db.php';
    
    $sql = "SELECT id, username, email, first_name, last_name, created_at FROM users WHERE id = ?";
    return getSingleResult($GLOBALS['conn'], $sql, "i", [$_SESSION['user_id']]);
}

/**
 * Login user
 */
function loginUser($username, $password) {
    require_once __DIR__ . '/../config/db.php';
    
    try {
        // Get user by username or email
        $sql = "SELECT id, username, email, password, first_name, last_name FROM users WHERE username = ? OR email = ?";
        $user = getSingleResult($GLOBALS['conn'], $sql, "ss", [$username, $username]);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid username or password.'];
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid username or password.'];
        }
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        return ['success' => true, 'message' => 'Login successful!'];
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred during login.'];
    }
}

/**
 * Register new user
 */
function registerUser($username, $email, $password, $firstName, $lastName) {
    require_once __DIR__ . '/../config/db.php';
    
    try {
        // Check if username already exists
        $sql = "SELECT id FROM users WHERE username = ?";
        $existingUser = getSingleResult($GLOBALS['conn'], $sql, "s", [$username]);
        
        if ($existingUser) {
            return ['success' => false, 'message' => 'Username already exists.'];
        }
        
        // Check if email already exists
        $sql = "SELECT id FROM users WHERE email = ?";
        $existingEmail = getSingleResult($GLOBALS['conn'], $sql, "s", [$email]);
        
        if ($existingEmail) {
            return ['success' => false, 'message' => 'Email already exists.'];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $sql = "INSERT INTO users (username, email, password, first_name, last_name) VALUES (?, ?, ?, ?, ?)";
        $stmt = executeQuery($GLOBALS['conn'], $sql, "sssss", [$username, $email, $hashedPassword, $firstName, $lastName]);
        
        if ($stmt) {
            $stmt->close();
            return ['success' => true, 'message' => 'Registration successful! You can now log in.'];
        }
        
        return ['success' => false, 'message' => 'Registration failed.'];
        
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred during registration.'];
    }
}

/**
 * Logout user
 */
function logoutUser() {
    // Destroy session
    session_start();
    session_unset();
    session_destroy();
    
    // Start new session for flash message
    session_start();
    setFlashMessage('You have been logged out successfully.', 'success');
}

/**
 * Validate password strength
 */
function validatePassword($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter.';
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter.';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number.';
    }
    
    return $errors;
}

/**
 * Validate email format
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate username
 */
function validateUsername($username) {
    $errors = [];
    
    if (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters long.';
    }
    
    if (strlen($username) > 50) {
        $errors[] = 'Username must be no more than 50 characters long.';
    }
    
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username can only contain letters, numbers, and underscores.';
    }
    
    return $errors;
}

/**
 * Set flash message
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Get base path for redirects
 */
function getBasePath() {
    $path = dirname($_SERVER['PHP_SELF']);
    if ($path === '/' || $path === '\\') {
        return '/';
    }
    return rtrim($path, '/\\') . '/';
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

?>
