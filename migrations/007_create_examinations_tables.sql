-- Migration: Create Examinations and ExamSchedule tables
-- Date: 2026-01-08
-- Description: Tables for managing examinations and their schedules

-- Examinations table: Stores exam definitions
CREATE TABLE IF NOT EXISTS Examinations (
    examID INT AUTO_INCREMENT PRIMARY KEY,
    examName VARCHAR(100) NOT NULL,
    examType ENUM('CAT', 'MidTerm', 'EndTerm', 'Mock', 'Final', 'Practice') NOT NULL,
    term TINYINT NOT NULL CHECK (term BETWEEN 1 AND 3),
    academicYear VARCHAR(9) NOT NULL,
    startDate DATE NOT NULL,
    endDate DATE NOT NULL,
    totalMarks INT NOT NULL DEFAULT 100,
    passingMarks INT NOT NULL DEFAULT 40,
    instructions TEXT,
    status ENUM('Scheduled', 'Ongoing', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    createdBy INT NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_term_year (term, academicYear),
    INDEX idx_dates (startDate, endDate),
    INDEX idx_status (status),
    INDEX idx_exam_type (examType),
    
    CONSTRAINT fk_exam_created_by FOREIGN KEY (createdBy) REFERENCES Users(userID) ON DELETE RESTRICT,
    CONSTRAINT chk_date_range CHECK (endDate >= startDate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ExamSchedule table: Maps exams to classes and subjects with specific details
CREATE TABLE IF NOT EXISTS ExamSchedule (
    scheduleID INT AUTO_INCREMENT PRIMARY KEY,
    examID INT NOT NULL,
    classID INT NOT NULL,
    subjectID INT NOT NULL,
    examDate DATE NOT NULL,
    startTime TIME NOT NULL,
    endTime TIME NOT NULL,
    room VARCHAR(50),
    invigilator INT,
    maxMarks INT NOT NULL DEFAULT 100,
    duration INT NOT NULL COMMENT 'Duration in minutes',
    specialInstructions TEXT,
    status ENUM('Scheduled', 'Ongoing', 'Completed', 'Postponed', 'Cancelled') DEFAULT 'Scheduled',
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_exam (examID),
    INDEX idx_class (classID),
    INDEX idx_subject (subjectID),
    INDEX idx_date_time (examDate, startTime),
    INDEX idx_invigilator (invigilator),
    INDEX idx_status (status),
    
    CONSTRAINT fk_schedule_exam FOREIGN KEY (examID) REFERENCES Examinations(examID) ON DELETE CASCADE,
    CONSTRAINT fk_schedule_class FOREIGN KEY (classID) REFERENCES Classes(classID) ON DELETE CASCADE,
    CONSTRAINT fk_schedule_subject FOREIGN KEY (subjectID) REFERENCES Subjects(subjectID) ON DELETE CASCADE,
    CONSTRAINT fk_schedule_invigilator FOREIGN KEY (invigilator) REFERENCES Users(userID) ON DELETE SET NULL,
    CONSTRAINT chk_time_range CHECK (endTime > startTime),
    
    UNIQUE KEY unique_class_subject_time (examDate, startTime, classID, subjectID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample examinations for current term
INSERT INTO Examinations (examName, examType, term, academicYear, startDate, endDate, totalMarks, passingMarks, instructions, status, createdBy) VALUES
('Term 1 CAT 1', 'CAT', 1, '2025/2026', '2026-02-10', '2026-02-14', 30, 12, 'First continuous assessment test for Term 1. Duration: 1 hour per subject.', 'Scheduled', 1),
('Term 1 Mid-Term Exam', 'MidTerm', 1, '2025/2026', '2026-03-16', '2026-03-20', 50, 20, 'Mid-term examination covering topics from January to March.', 'Scheduled', 1),
('Term 1 End-Term Exam', 'EndTerm', 1, '2025/2026', '2026-04-20', '2026-04-27', 100, 40, 'Final examination for Term 1. All topics from January to April.', 'Scheduled', 1);
