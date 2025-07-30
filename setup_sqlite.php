<?php
/**
 * SQLite Database Setup - No Password Required!
 * Alternative to MySQL for easy development
 */

echo "<h1>🗄️ SQLite Database Setup</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .step { margin: 15px 0; padding: 10px; border-left: 4px solid #007bff; background: #f8f9fa; }
</style>";

try {
    echo "<div class='step'>";
    echo "<h3>Step 1: Creating SQLite Database</h3>";
    
    // Create SQLite database
    $dbFile = 'blog.sqlite';
    $pdo = new PDO("sqlite:$dbFile");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    echo "<div class='success'>✅ SQLite database created: $dbFile</div>";
    echo "</div>";

    echo "<div class='step'>";
    echo "<h3>Step 2: Creating Tables</h3>";
    
    // Create users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) UNIQUE,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(20) DEFAULT 'author',
            status VARCHAR(20) DEFAULT 'active',
            first_name VARCHAR(50),
            last_name VARCHAR(50),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<div class='success'>✅ Users table created</div>";
    
    // Create posts table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title VARCHAR(200) NOT NULL,
            content TEXT NOT NULL,
            author_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            is_published BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "<div class='success'>✅ Posts table created</div>";
    
    // Create security tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS login_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(50) NOT NULL,
            attempts INTEGER DEFAULT 1,
            last_attempt DATETIME DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45),
            user_agent TEXT
        )
    ");
    echo "<div class='success'>✅ Login attempts table created</div>";
    
    echo "</div>";

    echo "<div class='step'>";
    echo "<h3>Step 3: Creating Default Users</h3>";
    
    // Check if users exist
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
    
    if ($userCount == 0) {
        // Create default users (password: password123)
        $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
        
        $users = [
            ['admin', 'admin@example.com', $hashedPassword, 'admin', 'Admin', 'User'],
            ['testuser', 'test@example.com', $hashedPassword, 'author', 'Test', 'User'],
            ['blogger', 'blogger@example.com', $hashedPassword, 'author', 'Blog', 'Writer'],
            ['editor', 'editor@example.com', $hashedPassword, 'editor', 'Editor', 'User']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, first_name, last_name) VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($users as $user) {
            $stmt->execute($user);
            echo "<div class='success'>✅ User '{$user[0]}' created</div>";
        }
    } else {
        echo "<div class='info'>ℹ️ Users already exist ($userCount users found)</div>";
    }
    echo "</div>";

    echo "<div class='step'>";
    echo "<h3>Step 4: Creating Sample Posts</h3>";
    
    // Check if posts exist
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM posts");
    $postCount = $stmt->fetch()['count'];
    
    if ($postCount == 0) {
        $posts = [
            ['Welcome to Our Enhanced Blog', 'This is our amazing blog with enhanced security features including SQL injection protection, role-based access control, and comprehensive input validation. The application now uses SQLite for easy development and deployment.', 1, 1],
            ['Getting Started with CRUD Operations', 'Learn how to perform Create, Read, Update, and Delete operations in this comprehensive blog application. This tutorial covers all the basics you need to know about database operations.', 1, 1],
            ['Security Best Practices', 'Discover the security features implemented in this application including prepared statements, password hashing, session management, and role-based access control.', 2, 2],
            ['SQLite vs MySQL', 'Learn about the differences between SQLite and MySQL, and why SQLite is perfect for development and small to medium applications. No server setup required!', 3, 3],
            ['User Roles and Permissions', 'Understanding the four-tier user role system: Subscriber, Author, Editor, and Admin. Each role has specific permissions and capabilities within the application.', 4, 4]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO posts (title, content, author_id, user_id) VALUES (?, ?, ?, ?)");
        
        foreach ($posts as $post) {
            $stmt->execute($post);
            echo "<div class='success'>✅ Post '{$post[0]}' created</div>";
        }
    } else {
        echo "<div class='info'>ℹ️ Posts already exist ($postCount posts found)</div>";
    }
    echo "</div>";

    // Create SQLite database configuration
    $sqliteConfig = "<?php
/**
 * SQLite Database Configuration - No Password Required!
 * Enhanced Security CRUD Blog Application
 */

// SQLite database file
\$dbFile = 'blog.sqlite';

try {
    // Create PDO connection to SQLite
    \$pdo = new PDO(\"sqlite:\$dbFile\");
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Enable foreign key constraints
    \$pdo->exec('PRAGMA foreign_keys = ON');
    
} catch (PDOException \$e) {
    die('Database connection failed: ' . \$e->getMessage());
}

// Helper functions for database operations
function getSingleResult(\$pdo, \$sql, \$params = []) {
    try {
        \$stmt = \$pdo->prepare(\$sql);
        \$stmt->execute(\$params);
        return \$stmt->fetch();
    } catch (PDOException \$e) {
        error_log('Database error: ' . \$e->getMessage());
        return null;
    }
}

function getMultipleResults(\$pdo, \$sql, \$params = []) {
    try {
        \$stmt = \$pdo->prepare(\$sql);
        \$stmt->execute(\$params);
        return \$stmt->fetchAll();
    } catch (PDOException \$e) {
        error_log('Database error: ' . \$e->getMessage());
        return [];
    }
}

function executeQuery(\$pdo, \$sql, \$params = []) {
    try {
        \$stmt = \$pdo->prepare(\$sql);
        \$stmt->execute(\$params);
        return \$stmt;
    } catch (PDOException \$e) {
        error_log('Database error: ' . \$e->getMessage());
        return false;
    }
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

// For compatibility with existing code
\$conn = null; // SQLite uses PDO, not mysqli
?>";

    file_put_contents('config/db_sqlite.php', $sqliteConfig);
    echo "<div class='step'>";
    echo "<h3>Step 5: Configuration Created</h3>";
    echo "<div class='success'>✅ SQLite configuration saved to: config/db_sqlite.php</div>";
    echo "</div>";

    echo "<div class='step'>";
    echo "<h3>🎉 SQLite Setup Complete!</h3>";
    echo "<div class='success'>✅ Database setup successful with SQLite!</div>";
    echo "<div class='info'>";
    echo "<h4>✨ Advantages of SQLite:</h4>";
    echo "<ul>";
    echo "<li>🚀 <strong>No Password Required</strong> - Works immediately</li>";
    echo "<li>📁 <strong>File-Based</strong> - Single file database</li>";
    echo "<li>⚡ <strong>Fast Setup</strong> - No server configuration</li>";
    echo "<li>🔒 <strong>All Security Features</strong> - Same security as MySQL</li>";
    echo "<li>📱 <strong>Portable</strong> - Easy to backup and move</li>";
    echo "</ul>";
    
    echo "<h4>🔑 Login Credentials:</h4>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> username: <code>admin</code>, password: <code>password123</code></li>";
    echo "<li><strong>Editor:</strong> username: <code>editor</code>, password: <code>password123</code></li>";
    echo "<li><strong>Author:</strong> username: <code>testuser</code>, password: <code>password123</code></li>";
    echo "<li><strong>Blogger:</strong> username: <code>blogger</code>, password: <code>password123</code></li>";
    echo "</ul>";
    
    echo "<h4>🎯 Next Steps:</h4>";
    echo "<ol>";
    echo "<li><strong>Switch to SQLite:</strong> Copy <code>config/db_sqlite.php</code> to <code>config/db.php</code></li>";
    echo "<li><strong>Test Homepage:</strong> <a href='index.php' target='_blank'>Visit Homepage</a></li>";
    echo "<li><strong>Test Login:</strong> <a href='auth/login.php' target='_blank'>Login Page</a></li>";
    echo "<li><strong>Test Security:</strong> <a href='test_security.php' target='_blank'>Security Test</a></li>";
    echo "</ol>";
    
    echo "<h4>🔄 Switch to SQLite Now:</h4>";
    echo "<form method='post' style='margin: 10px 0;'>";
    echo "<input type='hidden' name='switch_to_sqlite' value='1'>";
    echo "<button type='submit' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
    echo "🔄 Switch to SQLite Database Now";
    echo "</button>";
    echo "</form>";
    echo "</div>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='error'>❌ SQLite setup failed: " . $e->getMessage() . "</div>";
}

// Handle switch to SQLite
if (isset($_POST['switch_to_sqlite'])) {
    if (copy('config/db_sqlite.php', 'config/db.php')) {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
        echo "<h3>🎉 Successfully Switched to SQLite!</h3>";
        echo "<p>Your application is now using SQLite database.</p>";
        echo "<p><strong>Test your application:</strong></p>";
        echo "<ul>";
        echo "<li><a href='index.php' target='_blank'>Homepage</a></li>";
        echo "<li><a href='auth/login.php' target='_blank'>Login</a></li>";
        echo "<li><a href='test_php.php' target='_blank'>PHP Test</a></li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div class='error'>❌ Failed to switch configuration files</div>";
    }
}
?>
