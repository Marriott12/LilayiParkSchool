-- SQL script to create Users table for RBAC
-- Run this after the existing tables are created

CREATE TABLE IF NOT EXISTS Users (
    userID INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'parent') NOT NULL,
    firstName VARCHAR(50) NOT NULL,
    lastName VARCHAR(50) NOT NULL,
    isActive ENUM('Y', 'N') NOT NULL DEFAULT 'Y',
    lastLogin DATETIME NULL,
    createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Link Teachers to Users (optional)
ALTER TABLE Teacher 
ADD COLUMN userID INT NULL AFTER teacherID,
ADD CONSTRAINT fk_teacher_user FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE SET NULL ON UPDATE CASCADE;

-- Link Parents to Users (optional)
ALTER TABLE Parent 
ADD COLUMN userID INT NULL AFTER parentID,
ADD CONSTRAINT fk_parent_user FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE SET NULL ON UPDATE CASCADE;

-- Create default admin user (password: admin123 - CHANGE IN PRODUCTION!)
-- Password hash for 'admin123'
INSERT INTO Users (username, email, password, role, firstName, lastName) 
VALUES (
    'admin', 
    'admin@lilayipark.edu.zm', 
    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyVVpVKXp.fC',
    'admin',
    'System',
    'Administrator'
) ON DUPLICATE KEY UPDATE username = username;
