-- Academic Management Suite Database Schema
-- Grades, Examinations, Timetables, and Analytics

-- =====================================================
-- Grades Table
-- =====================================================
CREATE TABLE IF NOT EXISTS Grades (
    gradeID INT PRIMARY KEY AUTO_INCREMENT,
    pupilID INT NOT NULL,
    subjectID INT NOT NULL,
    classID INT NOT NULL,
    term ENUM('1', '2', '3') NOT NULL,
    academicYear VARCHAR(9) NOT NULL, -- e.g., '2025-2026'
    examType ENUM('CAT', 'MidTerm', 'EndTerm', 'Mock', 'Final') NOT NULL,
    marks DECIMAL(5,2) NOT NULL,
    maxMarks DECIMAL(5,2) NOT NULL DEFAULT 100,
    grade VARCHAR(2) NULL, -- A, B, C, D, E, F
    gradePoint DECIMAL(3,2) NULL, -- 4.0, 3.5, etc.
    remarks TEXT NULL,
    recordedBy INT NOT NULL,
    recordedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (pupilID) REFERENCES Pupil(pupilID) ON DELETE CASCADE,
    FOREIGN KEY (subjectID) REFERENCES Subjects(subjectID) ON DELETE CASCADE,
    FOREIGN KEY (classID) REFERENCES Class(classID) ON DELETE CASCADE,
    FOREIGN KEY (recordedBy) REFERENCES Users(userID),
    
    INDEX idx_pupil_term (pupilID, term, academicYear),
    INDEX idx_class_subject (classID, subjectID, term),
    INDEX idx_exam_type (examType, term, academicYear),
    
    UNIQUE KEY unique_grade (pupilID, subjectID, term, academicYear, examType)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Examinations Table
-- =====================================================
CREATE TABLE IF NOT EXISTS Examinations (
    examID INT PRIMARY KEY AUTO_INCREMENT,
    examName VARCHAR(100) NOT NULL,
    examType ENUM('CAT', 'MidTerm', 'EndTerm', 'Mock', 'Final') NOT NULL,
    term ENUM('1', '2', '3') NOT NULL,
    academicYear VARCHAR(9) NOT NULL,
    startDate DATE NOT NULL,
    endDate DATE NOT NULL,
    status ENUM('Scheduled', 'Ongoing', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    description TEXT NULL,
    createdBy INT NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (createdBy) REFERENCES Users(userID),
    
    INDEX idx_term_year (term, academicYear),
    INDEX idx_status (status),
    INDEX idx_dates (startDate, endDate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Exam Schedule Table (subjects per exam)
-- =====================================================
CREATE TABLE IF NOT EXISTS ExamSchedule (
    scheduleID INT PRIMARY KEY AUTO_INCREMENT,
    examID INT NOT NULL,
    subjectID INT NOT NULL,
    examDate DATE NOT NULL,
    startTime TIME NOT NULL,
    endTime TIME NOT NULL,
    duration INT NOT NULL, -- in minutes
    room VARCHAR(50) NULL,
    invigilator INT NULL,
    
    FOREIGN KEY (examID) REFERENCES Examinations(examID) ON DELETE CASCADE,
    FOREIGN KEY (subjectID) REFERENCES Subjects(subjectID),
    FOREIGN KEY (invigilator) REFERENCES Teacher(teacherID),
    
    INDEX idx_exam (examID),
    INDEX idx_date (examDate),
    
    UNIQUE KEY unique_schedule (examID, subjectID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Timetable Table
-- =====================================================
CREATE TABLE IF NOT EXISTS Timetable (
    timetableID INT PRIMARY KEY AUTO_INCREMENT,
    classID INT NOT NULL,
    subjectID INT NOT NULL,
    teacherID INT NOT NULL,
    dayOfWeek ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday') NOT NULL,
    startTime TIME NOT NULL,
    endTime TIME NOT NULL,
    room VARCHAR(50) NULL,
    academicYear VARCHAR(9) NOT NULL,
    term ENUM('1', '2', '3') NOT NULL,
    isActive BOOLEAN DEFAULT TRUE,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (classID) REFERENCES Class(classID) ON DELETE CASCADE,
    FOREIGN KEY (subjectID) REFERENCES Subjects(subjectID),
    FOREIGN KEY (teacherID) REFERENCES Teacher(teacherID),
    
    INDEX idx_class_day (classID, dayOfWeek, isActive),
    INDEX idx_teacher_day (teacherID, dayOfWeek, isActive),
    INDEX idx_room (room, dayOfWeek),
    
    -- Prevent time conflicts for class
    UNIQUE KEY unique_class_time (classID, dayOfWeek, startTime, term, academicYear)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Report Card Comments Table
-- =====================================================
CREATE TABLE IF NOT EXISTS ReportComments (
    commentID INT PRIMARY KEY AUTO_INCREMENT,
    pupilID INT NOT NULL,
    term ENUM('1', '2', '3') NOT NULL,
    academicYear VARCHAR(9) NOT NULL,
    classTeacherComment TEXT NULL,
    headTeacherComment TEXT NULL,
    conduct ENUM('Excellent', 'Very Good', 'Good', 'Fair', 'Poor') NULL,
    attendance VARCHAR(50) NULL, -- e.g., '85 out of 90 days'
    promoted BOOLEAN NULL,
    nextClass VARCHAR(50) NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (pupilID) REFERENCES Pupil(pupilID) ON DELETE CASCADE,
    
    UNIQUE KEY unique_report (pupilID, term, academicYear)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Grading Scale Configuration
-- =====================================================
CREATE TABLE IF NOT EXISTS GradingScale (
    scaleID INT PRIMARY KEY AUTO_INCREMENT,
    minMarks DECIMAL(5,2) NOT NULL,
    maxMarks DECIMAL(5,2) NOT NULL,
    grade VARCHAR(2) NOT NULL,
    gradePoint DECIMAL(3,2) NOT NULL,
    description VARCHAR(50) NOT NULL,
    isActive BOOLEAN DEFAULT TRUE,
    
    INDEX idx_marks (minMarks, maxMarks)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default grading scale
INSERT INTO GradingScale (minMarks, maxMarks, grade, gradePoint, description) VALUES
(80, 100, 'A', 4.00, 'Excellent'),
(70, 79, 'B', 3.00, 'Very Good'),
(60, 69, 'C', 2.00, 'Good'),
(50, 59, 'D', 1.00, 'Fair'),
(40, 49, 'E', 0.50, 'Pass'),
(0, 39, 'F', 0.00, 'Fail');

-- =====================================================
-- Academic Settings
-- =====================================================
INSERT INTO Settings (settingKey, settingValue, description) VALUES
('current_academic_year', '2025-2026', 'Current academic year'),
('current_term', '1', 'Current term (1, 2, or 3)'),
('term_1_start', '2025-09-01', 'Term 1 start date'),
('term_1_end', '2025-12-15', 'Term 1 end date'),
('term_2_start', '2026-01-05', 'Term 2 start date'),
('term_2_end', '2026-04-10', 'Term 2 end date'),
('term_3_start', '2026-04-20', 'Term 3 start date'),
('term_3_end', '2026-07-25', 'Term 3 end date'),
('passing_mark', '40', 'Minimum passing mark'),
('max_absences', '15', 'Maximum allowed absences per term')
ON DUPLICATE KEY UPDATE settingValue = VALUES(settingValue);
