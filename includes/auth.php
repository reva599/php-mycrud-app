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
 * Check if user is logged in with session timeout
 */
function isLoggedIn()
{
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        return false;
    }

    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        logoutUser();
        return false;
    }

    // Update last activity
    $_SESSION['last_activity'] = time();

    return true;
}

/**
 * Check user role and permissions
 */
function hasRole($requiredRole)
{
    if (!isLoggedIn()) {
        return false;
    }

    return hasPermission($requiredRole, $_SESSION['user_role'] ?? null);
}

/**
 * Require specific role - redirect if insufficient permissions
 */
function requireRole($requiredRole)
{
    if (!isLoggedIn()) {
        setFlashMessage('Please log in to access this page.', 'warning');
        header('Location: ' . getBasePath() . 'auth/login.php');
        exit();
    }

    if (!hasRole($requiredRole)) {
        setFlashMessage('You do not have permission to access this page.', 'error');
        header('Location: ' . getBasePath() . 'dashboard.php');
        exit();
    }
}

/**
 * Require authentication - redirect to login if not logged in
 */
function requireAuth()
{
    if (!isLoggedIn()) {
        setFlashMessage('Please log in to access this page.', 'warning');
        header('Location: ' . getBasePath() . 'auth/login.php');
        exit();
    }
}

/**
 * Redirect if already logged in
 */
function redirectIfLoggedIn()
{
    if (isLoggedIn()) {
        header('Location: ' . getBasePath() . 'dashboard.php');
        exit();
    }
}

/**
 * Get current user information
 */
function getCurrentUser()
{
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
function loginUser($username, $password)
{
    require_once __DIR__ . '/../config/db.php';

    try {
        // Validate input
        $username = validateInput($username, 'username');
        if ($username === false) {
            return ['success' => false, 'message' => 'Invalid username format.'];
        }

        if (empty($password)) {
            return ['success' => false, 'message' => 'Password is required.'];
        }

        // Check rate limiting
        if (!checkLoginAttempts($username)) {
            return ['success' => false, 'message' => 'Too many failed login attempts. Please try again in 15 minutes.'];
        }

        // Get user by username or email with role and status
        $sql = "SELECT id, username, email, password, role, status, first_name, last_name FROM users WHERE (username = ? OR email = ?) AND status = 'active'";
        $user = getSingleResult($GLOBALS['conn'], $sql, "ss", [$username, $username]);

        if (!$user) {
            recordFailedLogin($username);
            return ['success' => false, 'message' => 'Invalid username or password.'];
        }

        // Verify password
        if (!verifyPassword($password, $user['password'])) {
            recordFailedLogin($username);

            // Log failed login attempt
            $stmt = $GLOBALS['conn']->prepare("INSERT INTO audit_log (user_id, action, ip_address, user_agent) VALUES (?, 'failed_login', ?, ?)");
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $stmt->bind_param("iss", $user['id'], $ip, $userAgent);
            $stmt->execute();

            return ['success' => false, 'message' => 'Invalid username or password.'];
        }

        // Clear failed login attempts
        clearLoginAttempts($username);

        // Regenerate session ID for security
        session_regenerate_id(true);

        // Set session variables with role and security info
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_status'] = $user['status'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();

        // Log successful login
        $stmt = $GLOBALS['conn']->prepare("INSERT INTO audit_log (user_id, action, ip_address, user_agent) VALUES (?, 'login', ?, ?)");
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $stmt->bind_param("iss", $user['id'], $ip, $userAgent);
        $stmt->execute();

        // Create session record
        $sessionId = session_id();
        $expiresAt = date('Y-m-d H:i:s', time() + SESSION_TIMEOUT);
        $stmt = $GLOBALS['conn']->prepare("INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user['id'], $sessionId, $ip, $userAgent, $expiresAt);
        $stmt->execute();

        return ['success' => true, 'message' => 'Login successful!', 'user' => $user];
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred during login.'];
    }
}

/**
 * Register new user
 */
function registerUser($username, $email, $password, $firstName, $lastName, $role = 'author')
{
    require_once __DIR__ . '/../config/db.php';

    try {
        // Validate and sanitize input
        $username = validateInput($username, 'username');
        $email = validateInput($email, 'email');
        $firstName = validateInput($firstName, 'string');
        $lastName = validateInput($lastName, 'string');

        // Validation checks
        if ($username === false) {
            return ['success' => false, 'message' => 'Username must be 3-50 characters and contain only letters, numbers, and underscores.'];
        }

        if ($email === false) {
            return ['success' => false, 'message' => 'Please enter a valid email address.'];
        }

        if (!validateInput($password, 'password')) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters long.'];
        }

        if (empty($firstName) || empty($lastName)) {
            return ['success' => false, 'message' => 'First name and last name are required.'];
        }

        // Validate role
        $validRoles = [ROLE_SUBSCRIBER, ROLE_AUTHOR, ROLE_EDITOR, ROLE_ADMIN];
        if (!in_array($role, $validRoles)) {
            $role = ROLE_AUTHOR; // Default role
        }

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

        // Hash password securely
        $hashedPassword = hashPassword($password);

        // Insert new user with role and status
        $sql = "INSERT INTO users (username, email, password, first_name, last_name, role, status) VALUES (?, ?, ?, ?, ?, ?, 'active')";
        $stmt = executeQuery($GLOBALS['conn'], $sql, "ssssss", [$username, $email, $hashedPassword, $firstName, $lastName, $role]);

        if ($stmt) {
            $userId = $GLOBALS['conn']->insert_id;
            $stmt->close();

            // Log user registration
            $stmt = $GLOBALS['conn']->prepare("INSERT INTO audit_log (user_id, action, ip_address, user_agent) VALUES (?, 'register', ?, ?)");
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $stmt->bind_param("iss", $userId, $ip, $userAgent);
            $stmt->execute();

            return ['success' => true, 'message' => 'Registration successful! You can now log in.', 'user_id' => $userId];
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
function logoutUser()
{
    require_once __DIR__ . '/../config/db.php';

    // Log logout action before destroying session
    if (isset($_SESSION['user_id'])) {
        try {
            $stmt = $GLOBALS['conn']->prepare("INSERT INTO audit_log (user_id, action, ip_address, user_agent) VALUES (?, 'logout', ?, ?)");
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $stmt->bind_param("iss", $_SESSION['user_id'], $ip, $userAgent);
            $stmt->execute();

            // Deactivate session record
            $sessionId = session_id();
            $stmt = $GLOBALS['conn']->prepare("UPDATE user_sessions SET is_active = FALSE WHERE session_id = ?");
            $stmt->bind_param("s", $sessionId);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Logout logging error: " . $e->getMessage());
        }
    }

    // Destroy session securely
    session_start();
    session_unset();
    session_destroy();

    // Clear session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // Start new session for flash message
    session_start();
    setFlashMessage('You have been logged out successfully.', 'success');
}

/**
 * Validate password strength
 */
function validatePassword($password)
{
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
function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate username
 */
function validateUsername($username)
{
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
function setFlashMessage($message, $type = 'info')
{
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Get base path for redirects
 */
function getBasePath()
{
    $path = dirname($_SERVER['PHP_SELF']);
    if ($path === '/' || $path === '\\') {
        return '/';
    }
    return rtrim($path, '/\\') . '/';
}

/**
 * Sanitize input data
 */
function sanitizeInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Generate CSRF token
 */
function generateCSRFToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
