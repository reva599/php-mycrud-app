<?php
/**
 * Database Setup Script for Enhanced Security CRUD Application
 * This script creates the database and tables with security features
 */

// Database configuration
$host = 'localhost';
$username = 'root';
$password = ''; // Default XAMPP MySQL password is empty
$database = 'blog';

echo "<h1>🔧 Database Setup for Enhanced Security CRUD Application</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .step { margin: 15px 0; padding: 10px; border-left: 4px solid #007bff; background: #f8f9fa; }
</style>";

try {
    // Step 1: Connect to MySQL server (without database)
    echo "<div class='step'>";
    echo "<h3>Step 1: Connecting to MySQL Server</h3>";
    $conn = new mysqli($host, $username, $password);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    echo "<div class='success'>✅ Connected to MySQL server successfully</div>";
    echo "</div>";

    // Step 2: Create database
    echo "<div class='step'>";
    echo "<h3>Step 2: Creating Database</h3>";
    $sql = "CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if ($conn->query($sql) === TRUE) {
        echo "<div class='success'>✅ Database '$database' created successfully</div>";
    } else {
        throw new Exception("Error creating database: " . $conn->error);
    }
    echo "</div>";

    // Step 3: Select database
    echo "<div class='step'>";
    echo "<h3>Step 3: Selecting Database</h3>";
    $conn->select_db($database);
    echo "<div class='success'>✅ Database '$database' selected</div>";
    echo "</div>";

    // Step 4: Create users table with security features
    echo "<div class='step'>";
    echo "<h3>Step 4: Creating Users Table</h3>";
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) UNIQUE,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(20) DEFAULT 'author',
        status VARCHAR(20) DEFAULT 'active',
        first_name VARCHAR(50),
        last_name VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_username (username),
        INDEX idx_email (email),
        INDEX idx_role (role),
        INDEX idx_status (status),
        CONSTRAINT chk_role CHECK (role IN ('admin', 'editor', 'author', 'subscriber')),
        CONSTRAINT chk_status CHECK (status IN ('active', 'inactive', 'banned'))
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql) === TRUE) {
        echo "<div class='success'>✅ Users table created successfully</div>";
    } else {
        throw new Exception("Error creating users table: " . $conn->error);
    }
    echo "</div>";

    // Step 5: Create posts table
    echo "<div class='step'>";
    echo "<h3>Step 5: Creating Posts Table</h3>";
    $sql = "CREATE TABLE IF NOT EXISTS posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        content TEXT NOT NULL,
        author_id INT NOT NULL,
        user_id INT NOT NULL, -- For backward compatibility
        is_published BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_author_id (author_id),
        INDEX idx_user_id (user_id),
        INDEX idx_created_at (created_at),
        INDEX idx_is_published (is_published)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql) === TRUE) {
        echo "<div class='success'>✅ Posts table created successfully</div>";
    } else {
        throw new Exception("Error creating posts table: " . $conn->error);
    }
    echo "</div>";

    // Step 6: Create security tables
    echo "<div class='step'>";
    echo "<h3>Step 6: Creating Security Tables</h3>";
    
    // Login attempts table
    $sql = "CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL,
        attempts INT DEFAULT 1,
        last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        ip_address VARCHAR(45),
        user_agent TEXT,
        UNIQUE KEY unique_username (username),
        INDEX idx_username (username),
        INDEX idx_last_attempt (last_attempt)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql) === TRUE) {
        echo "<div class='success'>✅ Login attempts table created</div>";
    } else {
        throw new Exception("Error creating login_attempts table: " . $conn->error);
    }

    // User sessions table
    $sql = "CREATE TABLE IF NOT EXISTS user_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        session_id VARCHAR(128) NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP,
        is_active BOOLEAN DEFAULT TRUE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_session (session_id),
        INDEX idx_user_id (user_id),
        INDEX idx_expires_at (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql) === TRUE) {
        echo "<div class='success'>✅ User sessions table created</div>";
    } else {
        throw new Exception("Error creating user_sessions table: " . $conn->error);
    }

    // Audit log table
    $sql = "CREATE TABLE IF NOT EXISTS audit_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        action VARCHAR(50) NOT NULL,
        table_name VARCHAR(50),
        record_id INT,
        old_values JSON,
        new_values JSON,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_user_id (user_id),
        INDEX idx_action (action),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql) === TRUE) {
        echo "<div class='success'>✅ Audit log table created</div>";
    } else {
        throw new Exception("Error creating audit_log table: " . $conn->error);
    }
    echo "</div>";

    // Step 7: Create default admin user
    echo "<div class='step'>";
    echo "<h3>Step 7: Creating Default Users</h3>";
    
    // Check if admin user exists
    $checkAdmin = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $adminUsername = 'admin';
    $checkAdmin->bind_param("s", $adminUsername);
    $checkAdmin->execute();
    $result = $checkAdmin->get_result();
    
    if ($result->num_rows == 0) {
        // Create admin user
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, status, first_name, last_name) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $email = 'admin@example.com';
        $role = 'admin';
        $status = 'active';
        $firstName = 'Admin';
        $lastName = 'User';
        $stmt->bind_param("sssssss", $adminUsername, $email, $adminPassword, $role, $status, $firstName, $lastName);
        
        if ($stmt->execute()) {
            echo "<div class='success'>✅ Admin user created (username: admin, password: admin123)</div>";
        } else {
            echo "<div class='error'>❌ Error creating admin user: " . $stmt->error . "</div>";
        }
    } else {
        echo "<div class='info'>ℹ️ Admin user already exists</div>";
    }

    // Create test users
    $testUsers = [
        ['editor', 'editor@example.com', 'editor123', 'editor', 'Editor', 'User'],
        ['author', 'author@example.com', 'author123', 'author', 'Author', 'User'],
        ['testuser', 'test@example.com', 'test123', 'author', 'Test', 'User']
    ];

    foreach ($testUsers as $user) {
        $checkUser = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $checkUser->bind_param("s", $user[0]);
        $checkUser->execute();
        $result = $checkUser->get_result();
        
        if ($result->num_rows == 0) {
            $hashedPassword = password_hash($user[2], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, status, first_name, last_name) VALUES (?, ?, ?, ?, 'active', ?, ?)");
            $stmt->bind_param("ssssss", $user[0], $user[1], $hashedPassword, $user[3], $user[4], $user[5]);
            
            if ($stmt->execute()) {
                echo "<div class='success'>✅ User '{$user[0]}' created (password: {$user[2]})</div>";
            } else {
                echo "<div class='error'>❌ Error creating user '{$user[0]}': " . $stmt->error . "</div>";
            }
        } else {
            echo "<div class='info'>ℹ️ User '{$user[0]}' already exists</div>";
        }
    }
    echo "</div>";

    // Step 8: Create sample posts
    echo "<div class='step'>";
    echo "<h3>Step 8: Creating Sample Posts</h3>";
    
    $samplePosts = [
        ['Welcome to Enhanced Security Blog', 'This blog now features comprehensive security including SQL injection protection, role-based access control, CSRF protection, and audit logging. All forms are validated and all database queries use prepared statements.', 1],
        ['Security Features Overview', 'Our enhanced blog application includes: 1) Prepared statements for SQL injection protection, 2) Role-based access control with 4 user levels, 3) CSRF token protection, 4) Rate limiting for brute force protection, 5) Comprehensive input validation, and 6) Audit logging for security monitoring.', 1],
        ['User Roles and Permissions', 'The system now supports four user roles: Subscriber (view only), Author (create/edit own posts), Editor (edit any post), and Admin (full system access). Each role has specific permissions enforced at both the application and database level.', 2]
    ];

    foreach ($samplePosts as $index => $post) {
        $checkPost = $conn->prepare("SELECT id FROM posts WHERE title = ?");
        $checkPost->bind_param("s", $post[0]);
        $checkPost->execute();
        $result = $checkPost->get_result();
        
        if ($result->num_rows == 0) {
            $stmt = $conn->prepare("INSERT INTO posts (title, content, author_id, user_id, is_published) VALUES (?, ?, ?, ?, 1)");
            $stmt->bind_param("ssii", $post[0], $post[1], $post[2], $post[2]);
            
            if ($stmt->execute()) {
                echo "<div class='success'>✅ Sample post '{$post[0]}' created</div>";
            } else {
                echo "<div class='error'>❌ Error creating post: " . $stmt->error . "</div>";
            }
        } else {
            echo "<div class='info'>ℹ️ Post '{$post[0]}' already exists</div>";
        }
    }
    echo "</div>";

    echo "<div class='step'>";
    echo "<h3>🎉 Database Setup Complete!</h3>";
    echo "<div class='success'>✅ All tables created successfully with security features</div>";
    echo "<div class='info'>";
    echo "<h4>Default Login Credentials:</h4>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> username: admin, password: admin123</li>";
    echo "<li><strong>Editor:</strong> username: editor, password: editor123</li>";
    echo "<li><strong>Author:</strong> username: author, password: author123</li>";
    echo "<li><strong>Test User:</strong> username: testuser, password: test123</li>";
    echo "</ul>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li>Visit <a href='auth/login.php'>Login Page</a> to test authentication</li>";
    echo "<li>Visit <a href='admin/dashboard.php'>Admin Dashboard</a> (login as admin first)</li>";
    echo "<li>Visit <a href='test_security.php'>Security Test Page</a> to verify all features</li>";
    echo "<li>Visit <a href='index.php'>Homepage</a> to see the enhanced blog</li>";
    echo "</ol>";
    echo "</div>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='error'>❌ Database setup failed: " . $e->getMessage() . "</div>";
    echo "<div class='info'>";
    echo "<h4>Troubleshooting:</h4>";
    echo "<ul>";
    echo "<li>Make sure XAMPP MySQL service is running</li>";
    echo "<li>Check if MySQL is accessible at localhost:3306</li>";
    echo "<li>Verify MySQL root user has no password (default XAMPP setup)</li>";
    echo "<li>Try restarting XAMPP services</li>";
    echo "</ul>";
    echo "</div>";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>

<div style="margin-top: 30px; padding: 15px; background: #e7f3ff; border-left: 4px solid #007bff;">
    <h4>🔧 Manual Database Setup (Alternative)</h4>
    <p>If this script doesn't work, you can manually create the database:</p>
    <ol>
        <li>Open <strong>phpMyAdmin</strong> at <a href="http://localhost/phpmyadmin">http://localhost/phpmyadmin</a></li>
        <li>Create a new database named <strong>"blog"</strong></li>
        <li>Import the SQL file: <strong>database/security_schema.sql</strong></li>
        <li>Or run the SQL commands from the file manually</li>
    </ol>
</div>
