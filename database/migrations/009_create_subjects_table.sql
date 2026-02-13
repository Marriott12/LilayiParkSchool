-- Subjects Table Migration
-- Creates table for school subjects

CREATE TABLE IF NOT EXISTS Subjects (
    subjectID INT PRIMARY KEY AUTO_INCREMENT,
    subjectCode VARCHAR(10) UNIQUE NOT NULL,
    subjectName VARCHAR(100) NOT NULL,
    description TEXT NULL,
    isActive BOOLEAN DEFAULT TRUE,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (subjectCode),
    INDEX idx_active (isActive)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default subjects
INSERT INTO Subjects (subjectCode, subjectName, description) VALUES
('ENG', 'English Language', 'English Language and Literature'),
('MATH', 'Mathematics', 'Mathematics'),
('SCI', 'Science', 'Integrated Science'),
('SST', 'Social Studies', 'Social Studies'),
('CRE', 'Religious Education', 'Christian Religious Education'),
('PE', 'Physical Education', 'Physical Education and Sports'),
('ART', 'Creative Arts', 'Art and Music')
ON DUPLICATE KEY UPDATE subjectName = VALUES(subjectName);
