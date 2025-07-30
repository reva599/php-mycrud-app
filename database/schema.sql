-- PHP CRUD Blog Application Database Schema
-- ApexPlanet Internship Project
-- 
-- This file contains the complete database schema for the blog application
-- including users and posts tables with proper relationships and constraints.

-- Create database
CREATE DATABASE IF NOT EXISTS blog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the blog database
USE blog;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    
    -- Indexes for better performance
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create posts table
CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    author_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_published BOOLEAN DEFAULT TRUE,
    
    -- Foreign key constraint
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    
    -- Indexes for better performance
    INDEX idx_author_id (author_id),
    INDEX idx_created_at (created_at),
    INDEX idx_title (title),
    INDEX idx_published (is_published)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create sessions table for session management (optional but recommended)
CREATE TABLE IF NOT EXISTS user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    
    -- Foreign key constraint
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    
    -- Indexes
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data for testing
INSERT INTO users (username, email, password, first_name, last_name) VALUES
('admin', 'admin@blog.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User'),
('john_doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Doe'),
('jane_smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane', 'Smith');

-- Note: The password hash above is for 'password' - change in production!

INSERT INTO posts (title, content, author_id) VALUES
('Welcome to Our Blog', 'This is the first post on our new blog platform. We are excited to share our thoughts and ideas with you!', 1),
('Getting Started with PHP', 'PHP is a powerful server-side scripting language that is especially suited for web development. In this post, we will explore the basics of PHP programming.', 2),
('Database Design Best Practices', 'When designing a database, it is important to follow certain best practices to ensure data integrity, performance, and scalability.', 3),
('Introduction to CRUD Operations', 'CRUD stands for Create, Read, Update, and Delete. These are the four basic operations that can be performed on data in a database.', 1),
('Web Security Fundamentals', 'Security is a critical aspect of web development. In this post, we will discuss some fundamental security practices every developer should know.', 2);

-- Create a view for posts with author information
CREATE VIEW posts_with_authors AS
SELECT 
    p.id,
    p.title,
    p.content,
    p.created_at,
    p.updated_at,
    p.is_published,
    u.username as author_username,
    u.first_name as author_first_name,
    u.last_name as author_last_name,
    CONCAT(u.first_name, ' ', u.last_name) as author_full_name
FROM posts p
JOIN users u ON p.author_id = u.id
WHERE p.is_published = TRUE
ORDER BY p.created_at DESC;

-- Create indexes for better performance
CREATE INDEX idx_posts_title_content ON posts(title, content(100));
CREATE INDEX idx_users_name ON users(first_name, last_name);

-- Show table structures
DESCRIBE users;
DESCRIBE posts;
DESCRIBE user_sessions;

-- Show sample data
SELECT 'Users Table:' as info;
SELECT id, username, email, first_name, last_name, created_at FROM users;

SELECT 'Posts Table:' as info;
SELECT id, title, LEFT(content, 50) as content_preview, author_id, created_at FROM posts;

SELECT 'Posts with Authors View:' as info;
SELECT id, title, author_full_name, created_at FROM posts_with_authors LIMIT 5;
