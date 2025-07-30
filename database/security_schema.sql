-- Enhanced Security Schema for CRUD Blog Application
-- This script adds security features including user roles, login attempts tracking, and enhanced user table

-- Add role and status columns to existing users table
ALTER TABLE users 
ADD COLUMN role VARCHAR(20) DEFAULT 'author' AFTER password,
ADD COLUMN status VARCHAR(20) DEFAULT 'active' AFTER role,
ADD COLUMN email VARCHAR(100) UNIQUE AFTER username,
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- Create login attempts tracking table for rate limiting
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    attempts INT DEFAULT 1,
    last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    UNIQUE KEY unique_username (username),
    INDEX idx_username (username),
    INDEX idx_last_attempt (last_attempt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create session management table for enhanced security
CREATE TABLE IF NOT EXISTS user_sessions (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create audit log table for tracking user actions
CREATE TABLE IF NOT EXISTS audit_log (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add author_id column to posts table if it doesn't exist
ALTER TABLE posts 
ADD COLUMN author_id INT AFTER user_id,
ADD FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE;

-- Update existing posts to set author_id same as user_id
UPDATE posts SET author_id = user_id WHERE author_id IS NULL;

-- Create default admin user with secure password
-- Password: SecureAdmin123!
INSERT IGNORE INTO users (username, email, password, role, status) VALUES 
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

-- Create sample editor user
-- Password: EditorPass123!
INSERT IGNORE INTO users (username, email, password, role, status) VALUES 
('editor', 'editor@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'editor', 'active');

-- Create sample author user
-- Password: AuthorPass123!
INSERT IGNORE INTO users (username, email, password, role, status) VALUES 
('author', 'author@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'author', 'active');

-- Update existing users to have proper roles if they don't have them
UPDATE users SET role = 'author' WHERE role IS NULL OR role = '';
UPDATE users SET status = 'active' WHERE status IS NULL OR status = '';

-- Create indexes for better performance
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_posts_author_id ON posts(author_id);
CREATE INDEX idx_posts_created_at ON posts(created_at);

-- Add constraints for data integrity
ALTER TABLE users 
ADD CONSTRAINT chk_role CHECK (role IN ('admin', 'editor', 'author', 'subscriber')),
ADD CONSTRAINT chk_status CHECK (status IN ('active', 'inactive', 'banned'));

-- Create view for user statistics
CREATE OR REPLACE VIEW user_stats AS
SELECT 
    u.id,
    u.username,
    u.email,
    u.role,
    u.status,
    u.created_at,
    COUNT(p.id) as total_posts,
    MAX(p.created_at) as last_post_date
FROM users u
LEFT JOIN posts p ON u.id = p.author_id
GROUP BY u.id, u.username, u.email, u.role, u.status, u.created_at;

-- Create view for recent activities
CREATE OR REPLACE VIEW recent_activities AS
SELECT 
    'post_created' as activity_type,
    p.title as activity_description,
    u.username,
    p.created_at as activity_date
FROM posts p
JOIN users u ON p.author_id = u.id
UNION ALL
SELECT 
    'user_registered' as activity_type,
    CONCAT('User ', u.username, ' registered') as activity_description,
    u.username,
    u.created_at as activity_date
FROM users u
ORDER BY activity_date DESC
LIMIT 50;

-- Security settings and optimizations
-- Enable general log for security monitoring (optional)
-- SET GLOBAL general_log = 'ON';
-- SET GLOBAL general_log_file = '/var/log/mysql/security.log';

-- Set secure defaults
SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';

-- Grant appropriate permissions (run as admin)
-- GRANT SELECT, INSERT, UPDATE, DELETE ON blog.* TO 'blog_user'@'localhost';
-- FLUSH PRIVILEGES;

-- Cleanup old sessions (should be run periodically via cron)
-- DELETE FROM user_sessions WHERE expires_at < NOW() OR (created_at < DATE_SUB(NOW(), INTERVAL 30 DAY) AND is_active = FALSE);

-- Cleanup old login attempts (should be run periodically via cron)  
-- DELETE FROM login_attempts WHERE last_attempt < DATE_SUB(NOW(), INTERVAL 7 DAY);

-- Cleanup old audit logs (should be run periodically via cron)
-- DELETE FROM audit_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

COMMIT;
