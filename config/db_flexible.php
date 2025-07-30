<?php
/**
 * Flexible Database Configuration for Enhanced Security CRUD Application
 * Handles different MySQL password scenarios
 */

// Database configuration - try different password combinations
$db_configs = [
    // Default XAMPP (no password)
    ['host' => 'localhost', 'username' => 'root', 'password' => '', 'database' => 'blog'],
    // XAMPP with password set
    ['host' => 'localhost', 'username' => 'root', 'password' => 'root', 'database' => 'blog'],
    // Common XAMPP passwords
    ['host' => 'localhost', 'username' => 'root', 'password' => 'password', 'database' => 'blog'],
    ['host' => 'localhost', 'username' => 'root', 'password' => 'admin', 'database' => 'blog'],
];

$conn = null;
$connection_error = '';

// Try each configuration
foreach ($db_configs as $config) {
    try {
        $conn = new mysqli($config['host'], $config['username'], $config['password'], $config['database']);
        
        if ($conn->connect_error) {
            $connection_error = $conn->connect_error;
            continue;
        }
        
        // Set charset
        $conn->set_charset("utf8mb4");
        
        // Test connection with a simple query
        $result = $conn->query("SELECT 1");
        if ($result) {
            // Connection successful
            break;
        }
        
    } catch (Exception $e) {
        $connection_error = $e->getMessage();
        continue;
    }
}

// If no connection worked, show error
if (!$conn || $conn->connect_error) {
    die("
    <div style='font-family: Arial, sans-serif; margin: 20px; padding: 20px; border: 1px solid #dc3545; background: #f8d7da; color: #721c24; border-radius: 5px;'>
        <h3>🔧 Database Connection Failed</h3>
        <p><strong>Error:</strong> $connection_error</p>
        
        <h4>Quick Fix Options:</h4>
        <ol>
            <li><strong>Use phpMyAdmin:</strong>
                <ul>
                    <li>Go to <a href='http://localhost/phpmyadmin' target='_blank'>http://localhost/phpmyadmin</a></li>
                    <li>Create a database named 'blog'</li>
                    <li>Import the file: <code>database/setup_manual.sql</code></li>
                </ul>
            </li>
            <li><strong>Reset MySQL Password:</strong>
                <ul>
                    <li>Open XAMPP Control Panel</li>
                    <li>Stop MySQL service</li>
                    <li>Click 'Config' → 'my.ini'</li>
                    <li>Add <code>skip-grant-tables</code> under [mysqld]</li>
                    <li>Restart MySQL and reset password</li>
                </ul>
            </li>
            <li><strong>Manual Configuration:</strong>
                <ul>
                    <li>Edit <code>config/db.php</code></li>
                    <li>Update the database credentials</li>
                    <li>Set the correct MySQL password</li>
                </ul>
            </li>
        </ol>
        
        <h4>Test Database Connection:</h4>
        <p>Try these credentials in phpMyAdmin:</p>
        <ul>
            <li>Username: <code>root</code>, Password: <code>(empty)</code></li>
            <li>Username: <code>root</code>, Password: <code>root</code></li>
            <li>Username: <code>root</code>, Password: <code>password</code></li>
        </ul>
    </div>
    ");
}

// Security constants and functions (same as before)
define('DEVELOPMENT_MODE', true);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900);
define('SESSION_TIMEOUT', 3600);
define('PASSWORD_MIN_LENGTH', 8);
define('USERNAME_MIN_LENGTH', 3);
define('USERNAME_MAX_LENGTH', 50);

// User Roles
define('ROLE_ADMIN', 'admin');
define('ROLE_EDITOR', 'editor');
define('ROLE_AUTHOR', 'author');
define('ROLE_SUBSCRIBER', 'subscriber');

// User Status
define('STATUS_ACTIVE', 'active');
define('STATUS_INACTIVE', 'inactive');
define('STATUS_BANNED', 'banned');

/**
 * Enhanced Security Functions
 */
function validateInput($data, $type = 'string', $options = []) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    switch ($type) {
        case 'email':
            if (!filter_var($data, FILTER_VALIDATE_EMAIL)) {
                return false;
            }
            break;
            
        case 'username':
            if (strlen($data) < USERNAME_MIN_LENGTH || strlen($data) > USERNAME_MAX_LENGTH) {
                return false;
            }
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $data)) {
                return false;
            }
            break;
            
        case 'password':
            if (strlen($data) < PASSWORD_MIN_LENGTH) {
                return false;
            }
            break;
            
        case 'title':
            $maxLength = isset($options['max_length']) ? $options['max_length'] : 200;
            if (empty($data) || strlen($data) > $maxLength) {
                return false;
            }
            break;
            
        case 'content':
            $maxLength = isset($options['max_length']) ? $options['max_length'] : 10000;
            if (empty($data) || strlen($data) > $maxLength) {
                return false;
            }
            break;
    }
    
    return $data;
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function hasPermission($requiredRole, $userRole = null) {
    if ($userRole === null) {
        $userRole = $_SESSION['user_role'] ?? null;
    }
    
    $roleHierarchy = [
        ROLE_SUBSCRIBER => 1,
        ROLE_AUTHOR => 2,
        ROLE_EDITOR => 3,
        ROLE_ADMIN => 4
    ];
    
    $userLevel = $roleHierarchy[$userRole] ?? 0;
    $requiredLevel = $roleHierarchy[$requiredRole] ?? 0;
    
    return $userLevel >= $requiredLevel;
}

function canModifyPost($postUserId, $currentUserId = null, $currentUserRole = null) {
    if ($currentUserId === null) {
        $currentUserId = $_SESSION['user_id'] ?? null;
    }
    
    if ($currentUserRole === null) {
        $currentUserRole = $_SESSION['user_role'] ?? null;
    }
    
    if ($currentUserRole === ROLE_ADMIN || $currentUserRole === ROLE_EDITOR) {
        return true;
    }
    
    if ($currentUserRole === ROLE_AUTHOR && $postUserId == $currentUserId) {
        return true;
    }
    
    return false;
}

function generateCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function checkLoginAttempts($username) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT attempts, last_attempt FROM login_attempts WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $timeDiff = time() - strtotime($row['last_attempt']);
        
        if ($row['attempts'] >= MAX_LOGIN_ATTEMPTS && $timeDiff < LOGIN_LOCKOUT_TIME) {
            return false;
        }
        
        if ($timeDiff >= LOGIN_LOCKOUT_TIME) {
            $stmt = $conn->prepare("DELETE FROM login_attempts WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
        }
    }
    
    return true;
}

function recordFailedLogin($username) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO login_attempts (username, attempts, last_attempt) VALUES (?, 1, NOW()) ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = NOW()");
    $stmt->bind_param("s", $username);
    $stmt->execute();
}

function clearLoginAttempts($username) {
    global $conn;
    
    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<!-- Database connection successful with enhanced security features -->";
?>
