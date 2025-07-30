-- Enhanced Security CRUD Blog Database Setup
-- Run this script in phpMyAdmin or MySQL command line

-- Create database
CREATE DATABASE IF NOT EXISTS `blog` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `blog`;

-- Create users table with security features
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` VARCHAR(20) DEFAULT 'author',
    `status` VARCHAR(20) DEFAULT 'active',
    `first_name` VARCHAR(50),
    `last_name` VARCHAR(50),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_username` (`username`),
    INDEX `idx_email` (`email`),
    INDEX `idx_role` (`role`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create posts table
CREATE TABLE IF NOT EXISTS `posts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `content` TEXT NOT NULL,
    `author_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `is_published` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_author_id` (`author_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_is_published` (`is_published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create login attempts table for rate limiting
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL,
    `attempts` INT DEFAULT 1,
    `last_attempt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    UNIQUE KEY `unique_username` (`username`),
    INDEX `idx_username` (`username`),
    INDEX `idx_last_attempt` (`last_attempt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create user sessions table
CREATE TABLE IF NOT EXISTS `user_sessions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `session_id` VARCHAR(128) NOT NULL,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `expires_at` TIMESTAMP,
    `is_active` BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_session` (`session_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create audit log table
CREATE TABLE IF NOT EXISTS `audit_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `action` VARCHAR(50) NOT NULL,
    `table_name` VARCHAR(50),
    `record_id` INT,
    `old_values` JSON,
    `new_values` JSON,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (password: admin123)
INSERT IGNORE INTO `users` (`username`, `email`, `password`, `role`, `status`, `first_name`, `last_name`) VALUES 
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', 'Admin', 'User');

-- Insert test users
INSERT IGNORE INTO `users` (`username`, `email`, `password`, `role`, `status`, `first_name`, `last_name`) VALUES 
('editor', 'editor@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'editor', 'active', 'Editor', 'User'),
('author', 'author@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'author', 'active', 'Author', 'User'),
('testuser', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'author', 'active', 'Test', 'User');

-- Insert sample posts
INSERT IGNORE INTO `posts` (`title`, `content`, `author_id`, `user_id`, `is_published`) VALUES 
('Welcome to Enhanced Security Blog', 'This blog now features comprehensive security including SQL injection protection, role-based access control, CSRF protection, and audit logging. All forms are validated and all database queries use prepared statements.', 1, 1, 1),
('Security Features Overview', 'Our enhanced blog application includes: 1) Prepared statements for SQL injection protection, 2) Role-based access control with 4 user levels, 3) CSRF token protection, 4) Rate limiting for brute force protection, 5) Comprehensive input validation, and 6) Audit logging for security monitoring.', 1, 1, 1),
('User Roles and Permissions', 'The system now supports four user roles: Subscriber (view only), Author (create/edit own posts), Editor (edit any post), and Admin (full system access). Each role has specific permissions enforced at both the application and database level.', 2, 2, 1),
('Advanced Input Validation', 'All user inputs are now validated using multi-layer security: 1) Client-side HTML5 validation, 2) Server-side PHP validation, 3) Database constraints, 4) XSS prevention with htmlspecialchars(), 5) SQL injection prevention with prepared statements.', 2, 2, 1),
('CSRF Protection Implementation', 'Cross-Site Request Forgery protection is implemented using secure tokens. Every form includes a CSRF token that is validated on the server side. Tokens are generated using cryptographically secure random functions.', 3, 3, 1);

-- Add role and status constraints (MySQL 8.0+)
-- ALTER TABLE `users` 
-- ADD CONSTRAINT `chk_role` CHECK (`role` IN ('admin', 'editor', 'author', 'subscriber')),
-- ADD CONSTRAINT `chk_status` CHECK (`status` IN ('active', 'inactive', 'banned'));

COMMIT;
