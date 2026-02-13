-- Add role column to auth_users table
ALTER TABLE auth_users ADD COLUMN role ENUM('user', 'admin') NOT NULL DEFAULT 'user' AFTER password;
