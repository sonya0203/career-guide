-- Create Database
CREATE DATABASE IF NOT EXISTS career_guide_db;
USE career_guide_db;


-- Create Auth Users Table for Authentication
CREATE TABLE IF NOT EXISTS auth_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert Sample Auth User (password: "password123")
INSERT INTO auth_users (full_name, email, password) VALUES
('Admin User', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
-- Add role column to auth_users table if Exiting database available
ALTER TABLE auth_users ADD COLUMN role ENUM('user', 'admin') NOT NULL DEFAULT 'user' AFTER password;