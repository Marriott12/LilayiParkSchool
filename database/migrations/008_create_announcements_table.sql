-- Announcements Table Migration
-- Creates table for school-wide announcements and notifications

CREATE TABLE IF NOT EXISTS Announcements (
    announcementID INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    targetAudience ENUM('all', 'teacher', 'parent', 'admin') NOT NULL DEFAULT 'all',
    isPinned BOOLEAN DEFAULT FALSE,
    status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    expiryDate DATE NULL,
    createdBy INT NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (createdBy) REFERENCES Users(userID) ON DELETE CASCADE,
    
    INDEX idx_status (status),
    INDEX idx_audience (targetAudience),
    INDEX idx_pinned (isPinned),
    INDEX idx_expiry (expiryDate),
    INDEX idx_created (createdAt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
