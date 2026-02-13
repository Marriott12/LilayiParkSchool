-- =====================================================
-- Lilayi Park School - Complete Database Schema
-- For Remote Server Deployment
-- Generated: January 13, 2026
-- MySQL/MariaDB 8.0+
-- =====================================================

-- Set character set and collation
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- =====================================================
-- CORE TABLES (Base Schema)
-- =====================================================

-- Teacher Table
CREATE TABLE IF NOT EXISTS Teacher (
    teacherID VARCHAR(10) PRIMARY KEY,
    SSN VARCHAR(20) UNIQUE NOT NULL,
    Tpin VARCHAR(20) UNIQUE NOT NULL,
    fName VARCHAR(50) NOT NULL,
    lName VARCHAR(50) NOT NULL,
    NRC VARCHAR(20) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    gender ENUM('M', 'F') NOT NULL,
    tczNo VARCHAR(50),
    userID VARCHAR(10) NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_teacher_email (email),
    INDEX idx_teacher_nrc (NRC),
    INDEX idx_teacher_user (userID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Parent Table
CREATE TABLE IF NOT EXISTS Parent (
    parentID VARCHAR(10) PRIMARY KEY,
    fName VARCHAR(50) NOT NULL,
    lName VARCHAR(50) NOT NULL,
    relation VARCHAR(50) NOT NULL,
    gender ENUM('M', 'F') NOT NULL,
    NRC VARCHAR(20) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email1 VARCHAR(100) NOT NULL,
    email2 VARCHAR(100),
    occupation VARCHAR(100),
    workplace VARCHAR(100),
    userID VARCHAR(10) NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_parent_nrc (NRC),
    INDEX idx_parent_email (email1),
    INDEX idx_parent_user (userID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pupil Table
CREATE TABLE IF NOT EXISTS Pupil (
    pupilID VARCHAR(10) PRIMARY KEY,
    fName VARCHAR(50) NOT NULL,
    sName VARCHAR(50) NOT NULL,
    gender ENUM('M', 'F') NOT NULL,
    DoB DATE NOT NULL,
    homeAddress VARCHAR(200) NOT NULL,
    homeArea VARCHAR(100) NOT NULL,
    medCondition TEXT,
    medAllergy TEXT,
    restrictions TEXT,
    prevSch VARCHAR(100),
    reason TEXT,
    parentID VARCHAR(10) NOT NULL,
    enrollDate DATE NOT NULL,
    transport ENUM('Y', 'N') NOT NULL DEFAULT 'N',
    lunch ENUM('Y', 'N') NOT NULL DEFAULT 'N',
    photo ENUM('Y', 'N') NOT NULL DEFAULT 'N',
    passPhoto VARCHAR(255),
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parentID) REFERENCES Parent(parentID) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_pupil_parent (parentID),
    INDEX idx_pupil_name (fName, sName),
    INDEX idx_pupil_enrolldate (enrollDate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Class Table
CREATE TABLE IF NOT EXISTS Class (
    classID VARCHAR(10) PRIMARY KEY,
    className VARCHAR(50) NOT NULL UNIQUE,
    teacherID VARCHAR(10),
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacherID) REFERENCES Teacher(teacherID) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_class_teacher (teacherID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pupil_Class Junction Table
CREATE TABLE IF NOT EXISTS Pupil_Class (
    pupilID VARCHAR(10) NOT NULL,
    classID VARCHAR(10) NOT NULL,
    enrollmentDate DATE DEFAULT (CURRENT_DATE),
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (pupilID, classID),
    FOREIGN KEY (pupilID) REFERENCES Pupil(pupilID) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (classID) REFERENCES Class(classID) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_pc_class (classID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fees Table
CREATE TABLE IF NOT EXISTS Fees (
    feeID INT AUTO_INCREMENT PRIMARY KEY,
    classID VARCHAR(10) NOT NULL,
    feeAmt DECIMAL(10, 2) NOT NULL,
    term VARCHAR(20) NOT NULL,
    year INT NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (classID) REFERENCES Class(classID) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY unique_class_term_year (classID, term, year),
    INDEX idx_fees_class (classID),
    INDEX idx_fees_term_year (term, year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payment Table
CREATE TABLE IF NOT EXISTS Payment (
    payID VARCHAR(10) PRIMARY KEY,
    pupilID VARCHAR(10) NOT NULL,
    classID VARCHAR(10) NOT NULL,
    pmtAmt DECIMAL(10, 2) NOT NULL,
    balance DECIMAL(10, 2) NOT NULL,
    paymentDate DATE NOT NULL,
    paymentMode VARCHAR(50) DEFAULT 'Cash',
    remark TEXT,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pupilID) REFERENCES Pupil(pupilID) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (classID) REFERENCES Class(classID) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_payment_pupil (pupilID),
    INDEX idx_payment_class (classID),
    INDEX idx_payment_date (paymentDate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attendance Table (Term Summary)
CREATE TABLE IF NOT EXISTS Attendance (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    term VARCHAR(20) NOT NULL,
    year INT NOT NULL,
    pupilID VARCHAR(10) NOT NULL,
    daysPresent INT DEFAULT 0,
    daysAbsent INT DEFAULT 0,
    remark TEXT,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pupilID) REFERENCES Pupil(pupilID) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY unique_pupil_term_year (pupilID, term, year),
    INDEX idx_attendance_pupil (pupilID),
    INDEX idx_attendance_term_year (term, year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- USERS AND AUTHENTICATION
-- =====================================================

-- Users Table
CREATE TABLE IF NOT EXISTS Users (
    userID INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    apiToken VARCHAR(64) NULL,
    apiTokenExpires DATETIME NULL,
    role ENUM('admin', 'teacher', 'parent') NOT NULL,
    firstName VARCHAR(50) NOT NULL,
    lastName VARCHAR(50) NOT NULL,
    isActive ENUM('Y', 'N') NOT NULL DEFAULT 'Y',
    mustChangePassword ENUM('Y', 'N') NOT NULL DEFAULT 'N',
    lastLogin DATETIME NULL,
    createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_apiToken (apiToken)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- RBAC SYSTEM
-- =====================================================

-- Roles Table
CREATE TABLE IF NOT EXISTS Roles (
    roleID VARCHAR(10) PRIMARY KEY,
    roleName VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- UserRoles Table
CREATE TABLE IF NOT EXISTS UserRoles (
    userRoleID INT PRIMARY KEY AUTO_INCREMENT,
    userID INT NOT NULL,
    roleID VARCHAR(10) NOT NULL,
    assignedBy INT NULL,
    assignedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE CASCADE,
    FOREIGN KEY (roleID) REFERENCES Roles(roleID) ON DELETE CASCADE,
    UNIQUE KEY unique_user_role (userID, roleID),
    INDEX idx_user_roles (userID),
    INDEX idx_role_users (roleID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permissions Table
CREATE TABLE IF NOT EXISTS Permissions (
    permissionID VARCHAR(10) PRIMARY KEY,
    permissionName VARCHAR(100) NOT NULL UNIQUE,
    module VARCHAR(50),
    action VARCHAR(50),
    description TEXT,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- RolePermissions Table
CREATE TABLE IF NOT EXISTS RolePermissions (
    rolePermissionID INT PRIMARY KEY AUTO_INCREMENT,
    roleID VARCHAR(10) NOT NULL,
    permissionID VARCHAR(10) NOT NULL,
    UNIQUE KEY unique_role_permission (roleID, permissionID),
    INDEX idx_role_permissions (roleID),
    INDEX idx_permission_roles (permissionID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ACADEMIC MANAGEMENT
-- =====================================================

-- Subjects Table
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

-- Grades Table
CREATE TABLE IF NOT EXISTS Grades (
    gradeID INT PRIMARY KEY AUTO_INCREMENT,
    pupilID VARCHAR(10) NOT NULL,
    subjectID INT NOT NULL,
    classID VARCHAR(10) NOT NULL,
    term ENUM('1', '2', '3') NOT NULL,
    academicYear VARCHAR(9) NOT NULL,
    examType ENUM('CAT', 'MidTerm', 'EndTerm', 'Mock', 'Final') NOT NULL,
    marks DECIMAL(5,2) NOT NULL,
    maxMarks DECIMAL(5,2) NOT NULL DEFAULT 100,
    grade VARCHAR(2) NULL,
    gradePoint DECIMAL(3,2) NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Examinations Table
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ExamSchedule Table
CREATE TABLE IF NOT EXISTS ExamSchedule (
    scheduleID INT PRIMARY KEY AUTO_INCREMENT,
    examID INT NOT NULL,
    subjectID INT NOT NULL,
    examDate DATE NOT NULL,
    startTime TIME NOT NULL,
    endTime TIME NOT NULL,
    duration INT NOT NULL,
    room VARCHAR(50) NULL,
    invigilator VARCHAR(10) NULL,
    FOREIGN KEY (examID) REFERENCES Examinations(examID) ON DELETE CASCADE,
    FOREIGN KEY (subjectID) REFERENCES Subjects(subjectID),
    FOREIGN KEY (invigilator) REFERENCES Teacher(teacherID),
    INDEX idx_exam (examID),
    INDEX idx_date (examDate),
    UNIQUE KEY unique_schedule (examID, subjectID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Timetable Table
CREATE TABLE IF NOT EXISTS Timetable (
    timetableID INT PRIMARY KEY AUTO_INCREMENT,
    classID VARCHAR(10) NOT NULL,
    subjectID INT NOT NULL,
    teacherID VARCHAR(10) NOT NULL,
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
    UNIQUE KEY unique_class_time (classID, dayOfWeek, startTime, term, academicYear)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ReportComments Table
CREATE TABLE IF NOT EXISTS ReportComments (
    commentID INT PRIMARY KEY AUTO_INCREMENT,
    pupilID VARCHAR(10) NOT NULL,
    term ENUM('1', '2', '3') NOT NULL,
    academicYear VARCHAR(9) NOT NULL,
    classTeacherComment TEXT NULL,
    headTeacherComment TEXT NULL,
    conduct ENUM('Excellent', 'Very Good', 'Good', 'Fair', 'Poor') NULL,
    attendance VARCHAR(50) NULL,
    promoted BOOLEAN NULL,
    nextClass VARCHAR(50) NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pupilID) REFERENCES Pupil(pupilID) ON DELETE CASCADE,
    UNIQUE KEY unique_report (pupilID, term, academicYear)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- GradingScale Table
CREATE TABLE IF NOT EXISTS GradingScale (
    scaleID INT PRIMARY KEY AUTO_INCREMENT,
    minMarks DECIMAL(5,2) NOT NULL,
    maxMarks DECIMAL(5,2) NOT NULL,
    grade VARCHAR(2) NOT NULL,
    gradePoint DECIMAL(3,2) NOT NULL,
    description VARCHAR(50) NOT NULL,
    isActive BOOLEAN DEFAULT TRUE,
    INDEX idx_marks (minMarks, maxMarks)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DAILY ATTENDANCE
-- =====================================================

-- DailyAttendance Table
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

-- =====================================================
-- LIBRARY MANAGEMENT
-- =====================================================

-- Books Table
CREATE TABLE IF NOT EXISTS Books (
    bookID INT PRIMARY KEY AUTO_INCREMENT,
    ISBN VARCHAR(20) UNIQUE NULL,
    title VARCHAR(200) NOT NULL,
    author VARCHAR(200) NOT NULL,
    publisher VARCHAR(200) NULL,
    publicationYear YEAR NULL,
    category VARCHAR(100) NOT NULL,
    totalCopies INT NOT NULL DEFAULT 1,
    availableCopies INT NOT NULL DEFAULT 1,
    shelfLocation VARCHAR(50) NULL,
    description TEXT NULL,
    coverImage VARCHAR(255) NULL,
    isActive BOOLEAN DEFAULT TRUE,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_title (title),
    INDEX idx_author (author),
    INDEX idx_category (category),
    INDEX idx_isbn (ISBN),
    INDEX idx_active (isActive)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- BorrowRecords Table
CREATE TABLE IF NOT EXISTS BorrowRecords (
    borrowID INT PRIMARY KEY AUTO_INCREMENT,
    bookID INT NOT NULL,
    pupilID VARCHAR(10) NOT NULL,
    borrowDate DATE NOT NULL,
    dueDate DATE NOT NULL,
    returnDate DATE NULL,
    status ENUM('borrowed', 'returned', 'overdue', 'lost') NOT NULL DEFAULT 'borrowed',
    fine DECIMAL(10,2) DEFAULT 0.00,
    remarks TEXT NULL,
    issuedBy INT NOT NULL,
    returnedTo INT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bookID) REFERENCES Books(bookID) ON DELETE CASCADE,
    FOREIGN KEY (pupilID) REFERENCES Pupil(pupilID) ON DELETE CASCADE,
    FOREIGN KEY (issuedBy) REFERENCES Users(userID),
    FOREIGN KEY (returnedTo) REFERENCES Users(userID),
    INDEX idx_pupil (pupilID),
    INDEX idx_book (bookID),
    INDEX idx_status (status),
    INDEX idx_dates (borrowDate, dueDate, returnDate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ANNOUNCEMENTS
-- =====================================================

-- Announcements Table
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

-- =====================================================
-- HOLIDAYS
-- =====================================================

-- Holidays Table
CREATE TABLE IF NOT EXISTS holidays (
    holidayID INT AUTO_INCREMENT PRIMARY KEY,
    holidayName VARCHAR(100) NOT NULL,
    startDate DATE NOT NULL,
    endDate DATE NOT NULL,
    description TEXT,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_dates (startDate, endDate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SETTINGS
-- =====================================================

-- Settings Table
CREATE TABLE IF NOT EXISTS settings (
    settingID INT AUTO_INCREMENT PRIMARY KEY,
    settingKey VARCHAR(100) UNIQUE NOT NULL,
    settingValue TEXT NULL,
    category VARCHAR(50) DEFAULT 'general',
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_settingKey (settingKey)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- AUTO-INCREMENT TRIGGERS
-- =====================================================

DELIMITER $$

-- Teacher ID Trigger
DROP TRIGGER IF EXISTS before_teacher_insert$$
CREATE TRIGGER before_teacher_insert
BEFORE INSERT ON Teacher
FOR EACH ROW
BEGIN
    IF NEW.teacherID IS NULL OR NEW.teacherID = '' THEN
        DECLARE next_id INT;
        SET next_id = COALESCE((SELECT MAX(CAST(SUBSTRING(teacherID, 4) AS UNSIGNED)) FROM Teacher), 0) + 1;
        SET NEW.teacherID = CONCAT('TCH', LPAD(next_id, 3, '0'));
    END IF;
END$$

-- Parent ID Trigger
DROP TRIGGER IF EXISTS before_parent_insert$$
CREATE TRIGGER before_parent_insert
BEFORE INSERT ON Parent
FOR EACH ROW
BEGIN
    IF NEW.parentID IS NULL OR NEW.parentID = '' THEN
        DECLARE next_id INT;
        SET next_id = COALESCE((SELECT MAX(CAST(SUBSTRING(parentID, 4) AS UNSIGNED)) FROM Parent), 0) + 1;
        SET NEW.parentID = CONCAT('PAR', LPAD(next_id, 3, '0'));
    END IF;
END$$

-- Pupil ID Trigger
DROP TRIGGER IF EXISTS before_pupil_insert$$
CREATE TRIGGER before_pupil_insert
BEFORE INSERT ON Pupil
FOR EACH ROW
BEGIN
    IF NEW.pupilID IS NULL OR NEW.pupilID = '' THEN
        DECLARE next_id INT;
        SET next_id = COALESCE((SELECT MAX(CAST(SUBSTRING(pupilID, 2) AS UNSIGNED)) FROM Pupil), 0) + 1;
        SET NEW.pupilID = CONCAT('L', LPAD(next_id, 3, '0'));
    END IF;
END$$

-- Class ID Trigger
DROP TRIGGER IF EXISTS before_class_insert$$
CREATE TRIGGER before_class_insert
BEFORE INSERT ON Class
FOR EACH ROW
BEGIN
    IF NEW.classID IS NULL OR NEW.classID = '' THEN
        DECLARE next_id INT;
        SET next_id = COALESCE((SELECT MAX(CAST(SUBSTRING(classID, 4) AS UNSIGNED)) FROM Class), 0) + 1;
        SET NEW.classID = CONCAT('CLS', LPAD(next_id, 3, '0'));
    END IF;
END$$

-- Payment ID Trigger
DROP TRIGGER IF EXISTS before_payment_insert$$
CREATE TRIGGER before_payment_insert
BEFORE INSERT ON Payment
FOR EACH ROW
BEGIN
    IF NEW.payID IS NULL OR NEW.payID = '' THEN
        DECLARE next_id INT;
        SET next_id = COALESCE((SELECT MAX(CAST(SUBSTRING(payID, 4) AS UNSIGNED)) FROM Payment), 0) + 1;
        SET NEW.payID = CONCAT('PAY', LPAD(next_id, 3, '0'));
    END IF;
END$$

DELIMITER ;

-- =====================================================
-- END OF SCHEMA
-- =====================================================
