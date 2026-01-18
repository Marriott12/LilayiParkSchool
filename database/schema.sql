-- Lilayi Park School Database Schema
-- MySQL 8.0+
-- 
-- Note: This schema uses MySQL triggers to auto-generate custom IDs
-- (pupilID, teacherID, parentID, classID, payID) in specific formats.
-- The triggers are defined at the end of this file.

-- Drop existing tables if they exist (in reverse order of dependencies)
DROP TABLE IF EXISTS Attendance;
DROP TABLE IF EXISTS Payment;
DROP TABLE IF EXISTS Fees;
DROP TABLE IF EXISTS Pupil_Class;
DROP TABLE IF EXISTS Class;
DROP TABLE IF EXISTS Pupil;
DROP TABLE IF EXISTS Parent;
DROP TABLE IF EXISTS Teacher;

-- Create Teacher Table
CREATE TABLE Teacher (
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
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_teacher_email (email),
    INDEX idx_teacher_nrc (NRC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Parent Table
CREATE TABLE Parent (
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
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_parent_nrc (NRC),
    INDEX idx_parent_email (email1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Pupil Table
CREATE TABLE Pupil (
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

-- Create Class Table
CREATE TABLE Class (
    classID VARCHAR(10) PRIMARY KEY,
    className VARCHAR(50) NOT NULL UNIQUE,
    teacherID VARCHAR(10),
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacherID) REFERENCES Teacher(teacherID) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_class_teacher (teacherID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Pupil_Class Junction Table
CREATE TABLE Pupil_Class (
    pupilID VARCHAR(10) NOT NULL,
    classID VARCHAR(10) NOT NULL,
    enrollmentDate DATE DEFAULT (CURRENT_DATE),
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (pupilID, classID),
    FOREIGN KEY (pupilID) REFERENCES Pupil(pupilID) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (classID) REFERENCES Class(classID) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_pc_class (classID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Fees Table
CREATE TABLE Fees (
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

-- Create Payment Table
CREATE TABLE Payment (
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

-- Create Attendance Table
CREATE TABLE Attendance (
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

-- Create triggers for auto-generating IDs

DELIMITER $$

-- Trigger for Teacher ID generation
CREATE TRIGGER before_teacher_insert
BEFORE INSERT ON Teacher
FOR EACH ROW
BEGIN
    DECLARE next_id INT;
    SET next_id = COALESCE((SELECT MAX(CAST(SUBSTRING(teacherID, 4) AS UNSIGNED)) FROM Teacher), 0) + 1;
    SET NEW.teacherID = CONCAT('TCH', LPAD(next_id, 3, '0'));
END$$

-- Trigger for Parent ID generation
CREATE TRIGGER before_parent_insert
BEFORE INSERT ON Parent
FOR EACH ROW
BEGIN
    DECLARE next_id INT;
    SET next_id = COALESCE((SELECT MAX(CAST(SUBSTRING(parentID, 4) AS UNSIGNED)) FROM Parent), 0) + 1;
    SET NEW.parentID = CONCAT('PAR', LPAD(next_id, 3, '0'));
END$$

-- Trigger for Pupil ID generation (L001, L002, etc.)
CREATE TRIGGER before_pupil_insert
BEFORE INSERT ON Pupil
FOR EACH ROW
BEGIN
    DECLARE next_id INT;
    SET next_id = COALESCE((SELECT MAX(CAST(SUBSTRING(pupilID, 2) AS UNSIGNED)) FROM Pupil), 0) + 1;
    SET NEW.pupilID = CONCAT('L', LPAD(next_id, 3, '0'));
END$$

-- Trigger for Class ID generation
CREATE TRIGGER before_class_insert
BEFORE INSERT ON Class
FOR EACH ROW
BEGIN
    DECLARE next_id INT;
    SET next_id = COALESCE((SELECT MAX(CAST(SUBSTRING(classID, 4) AS UNSIGNED)) FROM Class), 0) + 1;
    SET NEW.classID = CONCAT('CLS', LPAD(next_id, 3, '0'));
END$$

-- Trigger for Payment ID generation
CREATE TRIGGER before_payment_insert
BEFORE INSERT ON Payment
FOR EACH ROW
BEGIN
    DECLARE next_id INT;
    SET next_id = COALESCE((SELECT MAX(CAST(SUBSTRING(payID, 4) AS UNSIGNED)) FROM Payment), 0) + 1;
    SET NEW.payID = CONCAT('PAY', LPAD(next_id, 3, '0'));
END$$

DELIMITER ;
