-- Migration: Create Daily Attendance Table
-- This creates a separate table for daily attendance tracking
-- The existing Attendance table tracks term-based totals

-- Create Daily Attendance Table
CREATE TABLE IF NOT EXISTS DailyAttendance (
    attendanceID INT AUTO_INCREMENT PRIMARY KEY,
    pupilID VARCHAR(10) NOT NULL,
    attendanceDate DATE NOT NULL,
    status ENUM('Present', 'Absent', 'Late', 'Excused') NOT NULL DEFAULT 'Absent',
    remarks TEXT,
    markedBy INT,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pupilID) REFERENCES Pupil(pupilID) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (markedBy) REFERENCES Users(userID) ON DELETE SET NULL ON UPDATE CASCADE,
    UNIQUE KEY unique_pupil_date (pupilID, attendanceDate),
    INDEX idx_attendance_date (attendanceDate),
    INDEX idx_attendance_pupil (pupilID),
    INDEX idx_attendance_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
