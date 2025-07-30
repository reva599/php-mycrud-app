<?php
/**
 * MySQL Password Finder for XAMPP
 * Tests common password combinations
 */

echo "<h1>🔍 Finding MySQL Password</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; }
    .info { color: blue; }
    .test { margin: 10px 0; padding: 10px; border-left: 3px solid #ccc; }
</style>";

$host = 'localhost';
$username = 'root';
$database = 'mysql'; // Test with mysql system database

// Common XAMPP passwords to try
$passwords = [
    '',           // Empty (default XAMPP)
    'root',       // Common
    'password',   // Common
    'admin',      // Common
    'xampp',      // XAMPP specific
    '123456',     // Simple
    'mysql',      // MySQL
    'localhost'   // Sometimes used
];

$working_password = null;

echo "<h2>Testing Password Combinations...</h2>";

foreach ($passwords as $password) {
    echo "<div class='test'>";
    $display_password = empty($password) ? '(empty)' : $password;
    echo "<strong>Testing:</strong> Username: $username, Password: $display_password<br>";
    
    try {
        $conn = new mysqli($host, $username, $password, $database);
        
        if ($conn->connect_error) {
            echo "<span class='error'>❌ Failed: " . $conn->connect_error . "</span>";
        } else {
            echo "<span class='success'>✅ SUCCESS! Password found: $display_password</span>";
            $working_password = $password;
            $conn->close();
            break;
        }
    } catch (Exception $e) {
        echo "<span class='error'>❌ Error: " . $e->getMessage() . "</span>";
    }
    echo "</div>";
}

if ($working_password !== null) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
    echo "<h2>🎉 Password Found!</h2>";
    echo "<p><strong>Working Configuration:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Host:</strong> $host</li>";
    echo "<li><strong>Username:</strong> $username</li>";
    echo "<li><strong>Password:</strong> " . (empty($working_password) ? '(empty)' : $working_password) . "</li>";
    echo "</ul>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Go to <a href='http://localhost/phpmyadmin' target='_blank'>phpMyAdmin</a></li>";
    echo "<li>Login with: Username: <code>$username</code>, Password: <code>" . (empty($working_password) ? '(leave empty)' : $working_password) . "</code></li>";
    echo "<li>Create database named <code>blog</code></li>";
    echo "<li>Run the SQL commands to create tables</li>";
    echo "</ol>";
    
    // Create updated database config
    $config_content = "<?php
// Working Database Configuration
\$host = '$host';
\$username = '$username';
\$password = '$working_password';
\$database = 'blog';

try {
    \$conn = new mysqli(\$host, \$username, \$password, \$database);
    
    if (\$conn->connect_error) {
        throw new Exception('Connection failed: ' . \$conn->connect_error);
    }
    
    \$conn->set_charset('utf8mb4');
    
} catch (Exception \$e) {
    die('Database connection failed: ' . \$e->getMessage());
}

// Helper functions
function getSingleResult(\$conn, \$sql, \$types = '', \$params = []) {
    if (empty(\$params)) {
        \$result = \$conn->query(\$sql);
        return \$result ? \$result->fetch_assoc() : null;
    }
    
    \$stmt = \$conn->prepare(\$sql);
    if (\$stmt && !empty(\$types)) {
        \$stmt->bind_param(\$types, ...\$params);
    }
    
    if (\$stmt && \$stmt->execute()) {
        \$result = \$stmt->get_result();
        return \$result ? \$result->fetch_assoc() : null;
    }
    
    return null;
}

function getMultipleResults(\$conn, \$sql, \$types = '', \$params = []) {
    if (empty(\$params)) {
        \$result = \$conn->query(\$sql);
        return \$result ? \$result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    \$stmt = \$conn->prepare(\$sql);
    if (\$stmt && !empty(\$types)) {
        \$stmt->bind_param(\$types, ...\$params);
    }
    
    if (\$stmt && \$stmt->execute()) {
        \$result = \$stmt->get_result();
        return \$result ? \$result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    return [];
}

function executeQuery(\$conn, \$sql, \$types = '', \$params = []) {
    \$stmt = \$conn->prepare(\$sql);
    if (\$stmt && !empty(\$types)) {
        \$stmt->bind_param(\$types, ...\$params);
    }
    
    if (\$stmt && \$stmt->execute()) {
        return \$stmt;
    }
    
    return false;
}

// Session management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper functions
function sanitizeInput(\$input) {
    return htmlspecialchars(trim(\$input), ENT_QUOTES, 'UTF-8');
}

function setFlashMessage(\$message, \$type = 'info') {
    \$_SESSION['flash_message'] = \$message;
    \$_SESSION['flash_type'] = \$type;
}

function getFlashMessage() {
    if (isset(\$_SESSION['flash_message'])) {
        \$message = \$_SESSION['flash_message'];
        \$type = \$_SESSION['flash_type'] ?? 'info';
        unset(\$_SESSION['flash_message'], \$_SESSION['flash_type']);
        return ['message' => \$message, 'type' => \$type];
    }
    return null;
}

function isLoggedIn() {
    return isset(\$_SESSION['user_id']) && !empty(\$_SESSION['user_id']);
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
?>";
    
    echo "<h3>📁 Updated Database Configuration:</h3>";
    echo "<p>I'll create an updated config file for you:</p>";
    
    // Save the working configuration
    file_put_contents('config/db_working.php', $config_content);
    echo "<p><span class='success'>✅ Saved working configuration to: config/db_working.php</span></p>";
    echo "<p>You can copy this to config/db.php once the database is set up.</p>";
    
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
    echo "<h2>❌ No Working Password Found</h2>";
    echo "<p>None of the common passwords worked. Try these solutions:</p>";
    
    echo "<h3>Solution 1: Reset MySQL Password</h3>";
    echo "<ol>";
    echo "<li>Open XAMPP Control Panel</li>";
    echo "<li>Stop MySQL service</li>";
    echo "<li>Click 'Config' next to MySQL → 'my.ini'</li>";
    echo "<li>Add <code>skip-grant-tables</code> under [mysqld] section</li>";
    echo "<li>Save and restart MySQL</li>";
    echo "<li>Go to phpMyAdmin and reset root password</li>";
    echo "</ol>";
    
    echo "<h3>Solution 2: Use Command Line</h3>";
    echo "<p>Open Command Prompt as Administrator and run:</p>";
    echo "<pre>cd C:\\xampp\\mysql\\bin
mysql -u root -p
(try different passwords when prompted)</pre>";
    
    echo "<h3>Solution 3: Check XAMPP Documentation</h3>";
    echo "<p>Check if you set a custom password during XAMPP installation.</p>";
    echo "</div>";
}

echo "<hr>";
echo "<h2>🔧 Alternative: Use SQLite Instead</h2>";
echo "<p>If MySQL continues to be problematic, we can switch to SQLite which doesn't require passwords:</p>";
echo "<ol>";
echo "<li>SQLite is file-based and doesn't need a server</li>";
echo "<li>No password required</li>";
echo "<li>Perfect for development and testing</li>";
echo "<li>All your security features will still work</li>";
echo "</ol>";
echo "<p><a href='setup_sqlite.php'>Click here to set up SQLite version</a></p>";
?>

<script>
// Auto-refresh every 30 seconds to show progress
setTimeout(function() {
    location.reload();
}, 30000);
</script>
