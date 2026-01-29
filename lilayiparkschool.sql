-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jan 21, 2026 at 07:30 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lilayiparkschool`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
CREATE TABLE IF NOT EXISTS `announcements` (
  `announcementID` int NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `targetAudience` enum('all','teachers','parents','admin') DEFAULT 'all',
  `isPinned` tinyint(1) DEFAULT '0',
  `status` enum('draft','published') DEFAULT 'draft',
  `expiryDate` date DEFAULT NULL,
  `createdBy` int DEFAULT NULL,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`announcementID`),
  KEY `createdBy` (`createdBy`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`announcementID`, `title`, `content`, `targetAudience`, `isPinned`, `status`, `expiryDate`, `createdBy`, `createdAt`, `updatedAt`) VALUES
(1, 'Opening Day', 'Opening day 12 January 2026', 'all', 0, 'published', '2026-01-13', 1, '2026-01-09 11:59:30', '2026-01-09 11:59:30');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
CREATE TABLE IF NOT EXISTS `attendance` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `term` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `year` int NOT NULL,
  `pupilID` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `daysPresent` int DEFAULT '0',
  `daysAbsent` int DEFAULT '0',
  `remark` text COLLATE utf8mb4_unicode_ci,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `unique_pupil_term_year` (`pupilID`,`term`,`year`),
  UNIQUE KEY `attendance_pupil_i_d_term_year` (`pupilID`,`term`,`year`),
  KEY `idx_attendance_pupil` (`pupilID`),
  KEY `idx_attendance_term_year` (`term`,`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

DROP TABLE IF EXISTS `books`;
CREATE TABLE IF NOT EXISTS `books` (
  `bookID` int NOT NULL AUTO_INCREMENT,
  `ISBN` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `author` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `publisher` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `publicationYear` year DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `totalCopies` int NOT NULL DEFAULT '1',
  `availableCopies` int NOT NULL DEFAULT '1',
  `shelfLocation` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `coverImage` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `isActive` tinyint(1) DEFAULT '1',
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`bookID`),
  UNIQUE KEY `ISBN` (`ISBN`),
  KEY `idx_title` (`title`),
  KEY `idx_author` (`author`),
  KEY `idx_category` (`category`),
  KEY `idx_isbn` (`ISBN`),
  KEY `idx_active` (`isActive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `borrowrecords`
--

DROP TABLE IF EXISTS `borrowrecords`;
CREATE TABLE IF NOT EXISTS `borrowrecords` (
  `borrowID` int NOT NULL AUTO_INCREMENT,
  `bookID` int NOT NULL,
  `pupilID` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `borrowDate` date NOT NULL,
  `dueDate` date NOT NULL,
  `returnDate` date DEFAULT NULL,
  `status` enum('borrowed','returned','overdue','lost') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrowed',
  `fine` decimal(10,2) DEFAULT '0.00',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `issuedBy` int NOT NULL,
  `returnedTo` int DEFAULT NULL,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`borrowID`),
  KEY `issuedBy` (`issuedBy`),
  KEY `returnedTo` (`returnedTo`),
  KEY `idx_pupil` (`pupilID`),
  KEY `idx_book` (`bookID`),
  KEY `idx_status` (`status`),
  KEY `idx_dates` (`borrowDate`,`dueDate`,`returnDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class`
--

DROP TABLE IF EXISTS `class`;
CREATE TABLE IF NOT EXISTS `class` (
  `classID` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `className` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `teacherID` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  PRIMARY KEY (`classID`),
  UNIQUE KEY `className` (`className`),
  UNIQUE KEY `className_2` (`className`),
  UNIQUE KEY `className_3` (`className`),
  UNIQUE KEY `className_4` (`className`),
  KEY `idx_class_teacher` (`teacherID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `class`
--

INSERT INTO `class` (`classID`, `className`, `teacherID`, `createdAt`, `updatedAt`) VALUES
('CLS001', 'Baby Class', 'TCH001', '0000-00-00 00:00:00', '0000-00-00 00:00:00');

--
-- Triggers `class`
--
DROP TRIGGER IF EXISTS `before_class_insert`;
DELIMITER $$
CREATE TRIGGER `before_class_insert` BEFORE INSERT ON `class` FOR EACH ROW BEGIN
    DECLARE next_id INT;
    SET next_id = COALESCE((SELECT MAX(CAST(SUBSTRING(classID, 4) AS UNSIGNED)) FROM Class), 0) + 1;
    SET NEW.classID = CONCAT('CLS', LPAD(next_id, 3, '0'));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `classsubjects`
--

DROP TABLE IF EXISTS `classsubjects`;
CREATE TABLE IF NOT EXISTS `classsubjects` (
  `classID` int NOT NULL,
  `subjectID` int NOT NULL,
  `assignedDate` date DEFAULT (curdate()),
  PRIMARY KEY (`classID`,`subjectID`),
  KEY `subjectID` (`subjectID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dailyattendance`
--

DROP TABLE IF EXISTS `dailyattendance`;
CREATE TABLE IF NOT EXISTS `dailyattendance` (
  `attendanceID` int NOT NULL AUTO_INCREMENT,
  `pupilID` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attendanceDate` date NOT NULL,
  `status` enum('Present','Absent','Late','Excused') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Absent',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `markedBy` int DEFAULT NULL,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`attendanceID`),
  UNIQUE KEY `unique_pupil_date` (`pupilID`,`attendanceDate`),
  KEY `markedBy` (`markedBy`),
  KEY `idx_attendance_date` (`attendanceDate`),
  KEY `idx_attendance_pupil` (`pupilID`),
  KEY `idx_attendance_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `examinations`
--

DROP TABLE IF EXISTS `examinations`;
CREATE TABLE IF NOT EXISTS `examinations` (
  `examID` int NOT NULL AUTO_INCREMENT,
  `examName` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `examType` enum('CAT','MidTerm','EndTerm','Mock','Final','Practice') COLLATE utf8mb4_unicode_ci NOT NULL,
  `term` tinyint NOT NULL,
  `academicYear` varchar(9) COLLATE utf8mb4_unicode_ci NOT NULL,
  `startDate` date NOT NULL,
  `endDate` date NOT NULL,
  `totalMarks` int NOT NULL DEFAULT '100',
  `passingMarks` int NOT NULL DEFAULT '40',
  `instructions` text COLLATE utf8mb4_unicode_ci,
  `status` enum('Scheduled','Ongoing','Completed','Cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'Scheduled',
  `createdBy` int NOT NULL,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`examID`),
  KEY `idx_term_year` (`term`,`academicYear`),
  KEY `idx_dates` (`startDate`,`endDate`),
  KEY `idx_status` (`status`),
  KEY `idx_exam_type` (`examType`),
  KEY `fk_exam_created_by` (`createdBy`)
) ;

--
-- Dumping data for table `examinations`
--

INSERT INTO `examinations` (`examID`, `examName`, `examType`, `term`, `academicYear`, `startDate`, `endDate`, `totalMarks`, `passingMarks`, `instructions`, `status`, `createdBy`, `createdAt`, `updatedAt`) VALUES
(1, 'Term 1 CAT 1', 'CAT', 1, '2025/2026', '2026-02-10', '2026-02-14', 30, 12, 'First continuous assessment test for Term 1. Duration: 1 hour per subject.', 'Scheduled', 1, '2026-01-21 07:28:56', '2026-01-21 07:28:56'),
(2, 'Term 1 Mid-Term Exam', 'MidTerm', 1, '2025/2026', '2026-03-16', '2026-03-20', 50, 20, 'Mid-term examination covering topics from January to March.', 'Scheduled', 1, '2026-01-21 07:28:56', '2026-01-21 07:28:56'),
(3, 'Term 1 End-Term Exam', 'EndTerm', 1, '2025/2026', '2026-04-20', '2026-04-27', 100, 40, 'Final examination for Term 1. All topics from January to April.', 'Scheduled', 1, '2026-01-21 07:28:56', '2026-01-21 07:28:56');

-- --------------------------------------------------------

--
-- Table structure for table `examschedule`
--

DROP TABLE IF EXISTS `examschedule`;
CREATE TABLE IF NOT EXISTS `examschedule` (
  `scheduleID` int NOT NULL AUTO_INCREMENT,
  `examID` int NOT NULL,
  `classID` bigint DEFAULT NULL,
  `subjectID` int NOT NULL,
  `examDate` date NOT NULL,
  `startTime` time NOT NULL,
  `endTime` time NOT NULL,
  `room` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invigilator` int DEFAULT NULL,
  `maxMarks` int NOT NULL DEFAULT '100',
  `duration` int NOT NULL COMMENT 'Duration in minutes',
  `specialInstructions` text COLLATE utf8mb4_unicode_ci,
  `status` enum('Scheduled','Ongoing','Completed','Postponed','Cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'Scheduled',
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`scheduleID`),
  KEY `idx_exam` (`examID`),
  KEY `idx_class` (`classID`),
  KEY `idx_subject` (`subjectID`),
  KEY `idx_date_time` (`examDate`,`startTime`),
  KEY `idx_invigilator` (`invigilator`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fees`
--

DROP TABLE IF EXISTS `fees`;
CREATE TABLE IF NOT EXISTS `fees` (
  `feeID` int NOT NULL AUTO_INCREMENT,
  `classID` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `feeAmt` decimal(10,2) NOT NULL,
  `term` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `year` int NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  PRIMARY KEY (`feeID`),
  UNIQUE KEY `unique_class_term_year` (`classID`,`term`,`year`),
  UNIQUE KEY `fees_class_i_d_term_year` (`classID`,`term`,`year`),
  KEY `idx_fees_class` (`classID`),
  KEY `idx_fees_term_year` (`term`,`year`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fees`
--

INSERT INTO `fees` (`feeID`, `classID`, `feeAmt`, `term`, `year`, `createdAt`, `updatedAt`) VALUES
(1, 'CLS001', 5000.00, 'Term 1', 2026, '0000-00-00 00:00:00', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

DROP TABLE IF EXISTS `grades`;
CREATE TABLE IF NOT EXISTS `grades` (
  `gradeID` int NOT NULL AUTO_INCREMENT,
  `pupilID` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `classID` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subjectID` int NOT NULL,
  `term` enum('1','2','3') COLLATE utf8mb4_unicode_ci NOT NULL,
  `academicYear` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `examType` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `maxScore` decimal(5,2) DEFAULT '100.00',
  `grade` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `recordedBy` int DEFAULT NULL,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`gradeID`),
  KEY `idx_pupil` (`pupilID`),
  KEY `idx_class` (`classID`),
  KEY `idx_subject` (`subjectID`),
  KEY `idx_term_year` (`term`,`academicYear`),
  KEY `recordedBy` (`recordedBy`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `holidays`
--

DROP TABLE IF EXISTS `holidays`;
CREATE TABLE IF NOT EXISTS `holidays` (
  `holidayID` int NOT NULL AUTO_INCREMENT,
  `holidayName` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `holidayType` enum('term_break','public_holiday','school_event','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `startDate` date NOT NULL,
  `endDate` date NOT NULL,
  `academicYear` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `createdAt` datetime NOT NULL,
  PRIMARY KEY (`holidayID`),
  KEY `idx_holiday_dates` (`startDate`,`endDate`),
  KEY `idx_holiday_year` (`academicYear`),
  KEY `idx_holiday_type` (`holidayType`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `holidays`
--

INSERT INTO `holidays` (`holidayID`, `holidayName`, `holidayType`, `startDate`, `endDate`, `academicYear`, `description`, `createdAt`) VALUES
(1, 'New Year', 'public_holiday', '2026-01-01', '2026-01-01', '2025-2026', 'New Year\'s Day', '2026-01-09 15:22:39'),
(2, 'Easter Break', 'term_break', '2026-04-03', '2026-04-13', '2025-2026', 'Easter Holiday Break', '2026-01-09 15:22:39'),
(3, 'Labour Day', 'public_holiday', '2026-05-01', '2026-05-01', '2025-2026', 'Labour Day', '2026-01-09 15:22:39'),
(4, 'Mid-Year Break', 'term_break', '2026-08-01', '2026-08-23', '2025-2026', 'Term 2 Break', '2026-01-09 15:22:39'),
(5, 'Independence Day', 'public_holiday', '2026-10-24', '2026-10-24', '2025-2026', 'Zambia Independence Day', '2026-01-09 15:22:39'),
(6, 'End of Year Break', 'term_break', '2026-12-04', '2027-01-10', '2025-2026', 'Term 3 Break / Christmas Holiday', '2026-01-09 15:22:39');

-- --------------------------------------------------------

--
-- Table structure for table `parent`
--

DROP TABLE IF EXISTS `parent`;
CREATE TABLE IF NOT EXISTS `parent` (
  `parentID` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `userID` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fName` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lName` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `relation` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` enum('M','F') COLLATE utf8mb4_unicode_ci NOT NULL,
  `NRC` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email1` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email2` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `occupation` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `workplace` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  PRIMARY KEY (`parentID`),
  UNIQUE KEY `NRC` (`NRC`),
  UNIQUE KEY `NRC_2` (`NRC`),
  UNIQUE KEY `NRC_3` (`NRC`),
  UNIQUE KEY `NRC_4` (`NRC`),
  KEY `idx_parent_nrc` (`NRC`),
  KEY `idx_parent_email` (`email1`),
  KEY `idx_parent_user` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `parent`
--
DROP TRIGGER IF EXISTS `before_parent_insert`;
DELIMITER $$
CREATE TRIGGER `before_parent_insert` BEFORE INSERT ON `parent` FOR EACH ROW BEGIN
    DECLARE next_id INT;
    SET next_id = COALESCE((SELECT MAX(CAST(SUBSTRING(parentID, 4) AS UNSIGNED)) FROM Parent), 0) + 1;
    SET NEW.parentID = CONCAT('PAR', LPAD(next_id, 3, '0'));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

DROP TABLE IF EXISTS `payment`;
CREATE TABLE IF NOT EXISTS `payment` (
  `payID` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pupilID` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `feeID` int DEFAULT NULL,
  `classID` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pmtAmt` decimal(10,2) NOT NULL,
  `balance` decimal(10,2) NOT NULL,
  `paymentDate` date NOT NULL,
  `paymentMode` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Cash',
  `remark` text COLLATE utf8mb4_unicode_ci,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `term` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Term 1',
  `academicYear` varchar(9) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '2026',
  PRIMARY KEY (`payID`),
  KEY `idx_payment_pupil` (`pupilID`),
  KEY `idx_payment_class` (`classID`),
  KEY `idx_payment_date` (`paymentDate`),
  KEY `fk_payment_fees` (`feeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `payment`
--
DROP TRIGGER IF EXISTS `before_payment_insert`;
DELIMITER $$
CREATE TRIGGER `before_payment_insert` BEFORE INSERT ON `payment` FOR EACH ROW BEGIN
    DECLARE next_id INT;
    SET next_id = COALESCE((SELECT MAX(CAST(SUBSTRING(payID, 4) AS UNSIGNED)) FROM Payment), 0) + 1;
    SET NEW.payID = CONCAT('PAY', LPAD(next_id, 3, '0'));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE IF NOT EXISTS `permissions` (
  `permissionID` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `permissionName` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`permissionID`),
  UNIQUE KEY `permissionName` (`permissionName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`permissionID`, `permissionName`, `module`, `action`, `description`, `createdAt`) VALUES
('P001', 'manage_users', 'users', 'all', 'Create, read, update, delete user accounts', '2026-01-08 12:17:29'),
('P002', 'manage_roles', 'roles', 'all', 'Assign and revoke user roles', '2026-01-08 12:17:29'),
('P003', 'view_teachers', 'teachers', 'read', 'View teacher information', '2026-01-08 12:17:29'),
('P004', 'manage_teachers', 'teachers', 'all', 'Create, update, delete teachers', '2026-01-08 12:17:29'),
('P005', 'view_parents', 'parents', 'read', 'View parent information', '2026-01-08 12:17:29'),
('P006', 'manage_parents', 'parents', 'all', 'Create, update, delete parents', '2026-01-08 12:17:29'),
('P007', 'view_pupils', 'pupils', 'read', 'View pupil information', '2026-01-08 12:17:29'),
('P008', 'manage_pupils', 'pupils', 'all', 'Create, update, delete pupils', '2026-01-08 12:17:29'),
('P009', 'view_grades', 'grades', 'read', 'View student grades', '2026-01-08 12:17:29'),
('P010', 'manage_grades', 'grades', 'all', 'Enter and update student grades', '2026-01-08 12:17:29'),
('P011', 'view_classes', 'classes', 'read', 'View class information', '2026-01-08 12:17:29'),
('P012', 'manage_classes', 'classes', 'all', 'Create, update class assignments', '2026-01-08 12:17:29'),
('P013', 'view_fees', 'fees', 'read', 'View fee information', '2026-01-08 12:17:29'),
('P014', 'manage_fees', 'fees', 'all', 'Manage fee payments and records', '2026-01-08 12:17:29'),
('P015', 'view_library', 'library', 'read', 'View library resources', '2026-01-08 12:17:29'),
('P016', 'manage_library', 'library', 'all', 'Manage library books and lending', '2026-01-08 12:17:29'),
('P017', 'view_attendance', 'attendance', 'read', 'View attendance records', '2026-01-08 12:41:03'),
('P018', 'manage_attendance', 'attendance', 'all', 'Enter and update attendance', '2026-01-08 12:41:03'),
('P019', 'view_reports', 'reports', 'read', 'View academic and school reports', '2026-01-08 12:41:03'),
('P020', 'manage_reports', 'reports', 'all', 'Create and manage reports', '2026-01-08 12:41:03'),
('P023', 'view_subjects', 'subjects', 'view', 'View subjects', '2026-01-09 12:51:01'),
('P024', 'view_users', 'users', 'read', 'View user accounts and details', '2026-01-13 12:37:37');

-- --------------------------------------------------------

--
-- Table structure for table `pupil`
--

DROP TABLE IF EXISTS `pupil`;

CREATE TABLE IF NOT EXISTS `Pupil` (
  `pupilID` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fName` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` enum('M','F') COLLATE utf8mb4_unicode_ci NOT NULL,
  `DoB` date NOT NULL,
  `homeAddress` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `homeArea` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `medCondition` text COLLATE utf8mb4_unicode_ci,
  `medAllergy` text COLLATE utf8mb4_unicode_ci,
  `restrictions` text COLLATE utf8mb4_unicode_ci,
  `prevSch` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `parentID` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `enrollDate` date NOT NULL,
  `transport` enum('Y','N') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'N',
  `lunch` enum('Y','N') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'N',
  `photo` enum('Y','N') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'N',
  `passPhoto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  PRIMARY KEY (`pupilID`),
  KEY `idx_pupil_parent` (`parentID`),
  KEY `idx_pupil_name` (`fName`,`lName`),
  KEY `idx_pupil_enrolldate` (`enrollDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `pupil`
--

DROP TRIGGER IF EXISTS `before_pupil_insert`;
DELIMITER $$
CREATE TRIGGER `before_pupil_insert` BEFORE INSERT ON `Pupil` FOR EACH ROW BEGIN
  DECLARE next_id INT;
  SET next_id = COALESCE((SELECT MAX(CAST(SUBSTRING(pupilID, 2) AS UNSIGNED)) FROM Pupil), 0) + 1;
  SET NEW.pupilID = CONCAT('L', LPAD(next_id, 3, '0'));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `pupil_class`
--

DROP TABLE IF EXISTS `pupil_class`;
CREATE TABLE IF NOT EXISTS `pupil_class` (
  `pupilID` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `classID` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `enrollmentDate` date DEFAULT NULL,
  `createdAt` datetime NOT NULL,
  PRIMARY KEY (`pupilID`,`classID`),
  KEY `idx_pc_class` (`classID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reportcomments`
--

DROP TABLE IF EXISTS `reportcomments`;
CREATE TABLE IF NOT EXISTS `reportcomments` (
  `commentID` int NOT NULL AUTO_INCREMENT,
  `pupilID` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `classID` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `term` enum('1','2','3') COLLATE utf8mb4_unicode_ci NOT NULL,
  `academicYear` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `teacherComment` text COLLATE utf8mb4_unicode_ci,
  `headteacherComment` text COLLATE utf8mb4_unicode_ci,
  `conductRating` enum('Excellent','Very Good','Good','Fair','Poor') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attendance` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `promotion` enum('Promoted','Retained','Conditional') COLLATE utf8mb4_unicode_ci DEFAULT 'Promoted',
  `createdBy` int DEFAULT NULL,
  `updatedBy` int DEFAULT NULL,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`commentID`),
  UNIQUE KEY `unique_report` (`pupilID`,`term`,`academicYear`),
  KEY `idx_pupil` (`pupilID`),
  KEY `idx_class` (`classID`),
  KEY `idx_term_year` (`term`,`academicYear`),
  KEY `createdBy` (`createdBy`),
  KEY `updatedBy` (`updatedBy`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rolepermissions`
--

DROP TABLE IF EXISTS `rolepermissions`;
CREATE TABLE IF NOT EXISTS `rolepermissions` (
  `rolePermissionID` int NOT NULL AUTO_INCREMENT,
  `roleID` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `permissionID` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`rolePermissionID`),
  UNIQUE KEY `unique_role_permission` (`roleID`,`permissionID`),
  KEY `idx_role_permissions` (`roleID`),
  KEY `idx_permission_roles` (`permissionID`)
) ENGINE=InnoDB AUTO_INCREMENT=85 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rolepermissions`
--

INSERT INTO `rolepermissions` (`rolePermissionID`, `roleID`, `permissionID`) VALUES
(9, 'R001', 'P001'),
(7, 'R001', 'P002'),
(16, 'R001', 'P003'),
(8, 'R001', 'P004'),
(14, 'R001', 'P005'),
(5, 'R001', 'P006'),
(15, 'R001', 'P007'),
(6, 'R001', 'P008'),
(12, 'R001', 'P009'),
(3, 'R001', 'P010'),
(10, 'R001', 'P011'),
(1, 'R001', 'P012'),
(11, 'R001', 'P013'),
(2, 'R001', 'P014'),
(13, 'R001', 'P015'),
(4, 'R001', 'P016'),
(70, 'R001', 'P017'),
(71, 'R001', 'P018'),
(72, 'R001', 'P019'),
(73, 'R001', 'P020'),
(74, 'R001', 'P021'),
(75, 'R001', 'P022'),
(79, 'R001', 'P023'),
(84, 'R001', 'P024'),
(63, 'R002', 'P003'),
(55, 'R002', 'P007'),
(56, 'R002', 'P009'),
(57, 'R002', 'P010'),
(54, 'R002', 'P011'),
(62, 'R002', 'P015'),
(58, 'R002', 'P017'),
(59, 'R002', 'P018'),
(60, 'R002', 'P019'),
(61, 'R002', 'P020'),
(76, 'R002', 'P021'),
(80, 'R002', 'P023'),
(64, 'R003', 'P007'),
(65, 'R003', 'P009'),
(66, 'R003', 'P013'),
(69, 'R003', 'P015'),
(67, 'R003', 'P017'),
(68, 'R003', 'P019'),
(42, 'R004', 'P007'),
(43, 'R004', 'P013'),
(44, 'R004', 'P014'),
(45, 'R005', 'P015'),
(46, 'R005', 'P016');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `roleID` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `roleName` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`roleID`),
  UNIQUE KEY `roleName` (`roleName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`roleID`, `roleName`, `description`, `createdAt`, `updatedAt`) VALUES
('R001', 'admin', 'System Administrator - Full access to all modules and settings', '2026-01-08 12:16:20', '2026-01-08 12:16:20'),
('R002', 'teacher', 'Teacher - Access to teaching modules, grades, and class management', '2026-01-08 12:16:20', '2026-01-08 12:16:20'),
('R003', 'parent', 'Parent - Access to child information, grades, and communications', '2026-01-08 12:16:20', '2026-01-08 12:16:20'),
('R004', 'accountant', 'Accountant - Manage school finances and fee payments', '2026-01-08 12:16:20', '2026-01-08 12:16:20'),
('R005', 'librarian', 'Librarian - Manage library books and resources', '2026-01-08 12:16:20', '2026-01-08 12:16:20');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
CREATE TABLE IF NOT EXISTS `settings` (
  `settingID` int NOT NULL AUTO_INCREMENT,
  `settingKey` varchar(100) NOT NULL,
  `settingValue` text,
  `category` varchar(50) DEFAULT 'general',
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`settingID`),
  UNIQUE KEY `settingKey` (`settingKey`)
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`settingID`, `settingKey`, `settingValue`, `category`, `createdAt`, `updatedAt`) VALUES
(1, 'school_name', 'Lilayi Park School', 'school', '2026-01-07 14:49:22', '2026-01-09 14:05:20'),
(2, 'school_address', 'Lusaka', 'school', '2026-01-07 14:49:22', '2026-01-09 14:05:20'),
(3, 'school_phone', '+260973116866', 'school', '2026-01-07 14:49:22', '2026-01-09 14:05:20'),
(4, 'school_email', 'lilayiparkschool@gmail.com', 'school', '2026-01-07 14:49:22', '2026-01-09 14:05:20'),
(5, 'current_term', '1', 'academic', '2026-01-07 14:49:22', '2026-01-09 14:05:21'),
(6, 'academic_year', '2025/2026', 'academic', '2026-01-07 14:49:22', '2026-01-07 14:49:22'),
(7, 'attendance_threshold', '75', 'academic', '2026-01-07 14:49:22', '2026-01-09 14:05:21'),
(8, 'currency', 'ZMW', 'financial', '2026-01-07 14:49:22', '2026-01-09 14:05:21'),
(9, 'late_fee_penalty', '5', 'financial', '2026-01-07 14:49:22', '2026-01-09 14:05:21'),
(10, 'current_year', '2026', 'academic', '2026-01-07 14:52:46', '2026-01-07 14:52:46'),
(11, 'library_borrow_days', '14', 'library', '2026-01-09 12:39:49', '2026-01-09 12:39:49'),
(12, 'library_fine_per_day', '1.00', 'library', '2026-01-09 12:39:49', '2026-01-09 14:05:21'),
(13, 'library_max_books', '5', 'library', '2026-01-09 12:39:49', '2026-01-09 14:05:21'),
(14, 'school_motto', 'Explore - Learn - Achieve', 'school', '2026-01-09 14:01:23', '2026-01-09 14:05:20'),
(15, 'current_academic_year', '2025/2026', 'academic', '2026-01-09 14:01:23', '2026-01-09 14:05:21'),
(16, 'term1_start', '', 'academic', '2026-01-09 14:01:23', '2026-01-09 14:05:21'),
(17, 'term1_end', '', 'academic', '2026-01-09 14:01:23', '2026-01-09 14:05:21'),
(18, 'term2_start', '', 'academic', '2026-01-09 14:01:23', '2026-01-09 14:05:21'),
(19, 'term2_end', '', 'academic', '2026-01-09 14:01:23', '2026-01-09 14:05:21'),
(20, 'term3_start', '', 'academic', '2026-01-09 14:01:23', '2026-01-09 14:05:21'),
(21, 'term3_end', '', 'academic', '2026-01-09 14:01:23', '2026-01-09 14:05:21'),
(22, 'grade_a_min', '80', 'grading', '2026-01-09 14:01:23', '2026-01-09 14:05:21'),
(23, 'grade_b_min', '70', 'grading', '2026-01-09 14:01:23', '2026-01-09 14:05:21'),
(24, 'grade_c_min', '60', 'grading', '2026-01-09 14:01:23', '2026-01-09 14:05:21'),
(25, 'grade_d_min', '50', 'grading', '2026-01-09 14:01:23', '2026-01-09 14:05:21'),
(26, 'passing_grade', '50', 'grading', '2026-01-09 14:01:23', '2026-01-09 14:05:21'),
(27, 'library_loan_period', '21', 'library', '2026-01-09 14:01:23', '2026-01-09 14:05:21'),
(28, 'notifications_enabled', '0', 'notifications', '2026-01-09 14:01:23', '2026-01-09 14:05:21'),
(29, 'email_notifications', '0', 'notifications', '2026-01-09 14:01:23', '2026-01-09 14:05:21'),
(30, 'sms_notifications', '0', 'notifications', '2026-01-09 14:01:23', '2026-01-09 14:05:21'),
(31, 'sms_api_key', '', 'notifications', '2026-01-09 14:01:23', '2026-01-09 14:05:22'),
(32, 'smtp_host', '', 'email', '2026-01-09 14:01:23', '2026-01-09 14:05:22'),
(33, 'smtp_port', '587', 'email', '2026-01-09 14:01:23', '2026-01-09 14:05:22'),
(34, 'smtp_username', 'admin', 'email', '2026-01-09 14:01:23', '2026-01-09 14:05:22'),
(35, 'smtp_password', 'armis2025', 'email', '2026-01-09 14:01:23', '2026-01-09 14:05:22'),
(36, 'smtp_encryption', 'tls', 'email', '2026-01-09 14:01:23', '2026-01-09 14:05:22'),
(37, 'smtp_from_email', '', 'email', '2026-01-09 14:01:23', '2026-01-09 14:05:22'),
(38, 'smtp_from_name', 'Lilayi Park School', 'email', '2026-01-09 14:01:23', '2026-01-09 14:05:22'),
(39, 'send_account_emails', '0', 'email', '2026-01-09 14:01:23', '2026-01-09 14:05:22'),
(40, 'report_show_position', '1', 'reports', '2026-01-09 14:01:23', '2026-01-09 14:05:22'),
(41, 'report_show_average', '1', 'reports', '2026-01-09 14:01:23', '2026-01-09 14:05:22'),
(42, 'report_show_attendance', '1', 'reports', '2026-01-09 14:01:23', '2026-01-09 14:05:22'),
(43, 'report_head_signature', '', 'reports', '2026-01-09 14:01:23', '2026-01-09 14:05:22'),
(44, 'maintenance_mode', '0', 'system', '2026-01-09 14:01:23', '2026-01-09 14:05:22'),
(45, 'session_timeout', '30', 'system', '2026-01-09 14:01:23', '2026-01-09 14:05:22'),
(56, 'test_setting_1767967340', 'Test Value', 'test', '2026-01-09 14:02:20', '2026-01-09 14:02:20'),
(57, 'cache_test', 'value1', 'test', '2026-01-09 14:02:20', '2026-01-09 14:02:20');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

DROP TABLE IF EXISTS `subjects`;
CREATE TABLE IF NOT EXISTS `subjects` (
  `subjectID` int NOT NULL AUTO_INCREMENT,
  `subjectName` varchar(100) NOT NULL,
  `subjectCode` varchar(20) DEFAULT NULL,
  `teacherID` int DEFAULT NULL,
  `credits` int DEFAULT '1',
  `description` text,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`subjectID`),
  UNIQUE KEY `subjectCode` (`subjectCode`),
  KEY `teacherID` (`teacherID`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`subjectID`, `subjectName`, `subjectCode`, `teacherID`, `credits`, `description`, `createdAt`, `updatedAt`) VALUES
(1, 'English Language', 'ENG', NULL, 1, 'English Language and Literature', '2026-01-09 12:36:09', '2026-01-09 12:36:09'),
(2, 'Mathematics', 'MATH', NULL, 1, 'Mathematics', '2026-01-09 12:36:09', '2026-01-09 12:36:09'),
(3, 'Science', 'SCI', NULL, 1, 'Integrated Science', '2026-01-09 12:36:09', '2026-01-09 12:36:09'),
(4, 'Social Studies', 'SST', NULL, 1, 'Social Studies', '2026-01-09 12:36:09', '2026-01-09 12:36:09'),
(5, 'Religious Education', 'CRE', NULL, 1, 'Christian Religious Education', '2026-01-09 12:36:09', '2026-01-09 12:36:09'),
(6, 'Physical Education', 'PE', NULL, 1, 'Physical Education and Sports', '2026-01-09 12:36:09', '2026-01-09 12:36:09'),
(7, 'Creative Arts', 'ART', NULL, 1, 'Art and Music', '2026-01-09 12:36:09', '2026-01-09 12:36:09');

-- --------------------------------------------------------

--
-- Table structure for table `teacher`
--

DROP TABLE IF EXISTS `teacher`;
CREATE TABLE IF NOT EXISTS `teacher` (
  `teacherID` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `userID` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `lName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `NRC` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `SSN` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Tpin` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` enum('M','F') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tczNo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dateEmployed` date NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  PRIMARY KEY (`teacherID`),
  UNIQUE KEY `SSN` (`SSN`),
  UNIQUE KEY `Tpin` (`Tpin`),
  UNIQUE KEY `NRC` (`NRC`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `SSN_2` (`SSN`),
  UNIQUE KEY `Tpin_2` (`Tpin`),
  UNIQUE KEY `NRC_2` (`NRC`),
  UNIQUE KEY `email_2` (`email`),
  UNIQUE KEY `SSN_3` (`SSN`),
  UNIQUE KEY `Tpin_3` (`Tpin`),
  UNIQUE KEY `NRC_3` (`NRC`),
  UNIQUE KEY `email_3` (`email`),
  UNIQUE KEY `SSN_4` (`SSN`),
  UNIQUE KEY `Tpin_4` (`Tpin`),
  UNIQUE KEY `NRC_4` (`NRC`),
  UNIQUE KEY `email_4` (`email`),
  KEY `idx_teacher_email` (`email`),
  KEY `idx_teacher_nrc` (`NRC`),
  KEY `idx_teacher_user` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teacher`
--

INSERT INTO `teacher` (`teacherID`, `userID`, `fName`, `lName`, `NRC`, `SSN`, `Tpin`, `phone`, `email`, `gender`, `tczNo`, `dateEmployed`, `createdAt`, `updatedAt`) VALUES
('TCH001', '3', 'Test', 'Teacher', '1767865027', 'TEST1767865027', 'TPIN1767865027', '0977123456', 'test1767@example.com', 'M', '1767865027', '0000-00-00', '0000-00-00 00:00:00', '0000-00-00 00:00:00');

--
-- Triggers `teacher`
--
DROP TRIGGER IF EXISTS `before_teacher_insert`;
DELIMITER $$
CREATE TRIGGER `before_teacher_insert` BEFORE INSERT ON `teacher` FOR EACH ROW BEGIN
    DECLARE next_id INT;
    SET next_id = COALESCE((SELECT MAX(CAST(SUBSTRING(teacherID, 4) AS UNSIGNED)) FROM Teacher), 0) + 1;
    SET NEW.teacherID = CONCAT('TCH', LPAD(next_id, 3, '0'));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `timetable`
--

DROP TABLE IF EXISTS `timetable`;
CREATE TABLE IF NOT EXISTS `timetable` (
  `timetableID` int NOT NULL AUTO_INCREMENT,
  `classID` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subjectID` int NOT NULL,
  `teacherID` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dayOfWeek` enum('Monday','Tuesday','Wednesday','Thursday','Friday') COLLATE utf8mb4_unicode_ci NOT NULL,
  `startTime` time NOT NULL,
  `endTime` time NOT NULL,
  `room` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `term` enum('1','2','3') COLLATE utf8mb4_unicode_ci NOT NULL,
  `academicYear` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`timetableID`),
  KEY `idx_class` (`classID`),
  KEY `idx_subject` (`subjectID`),
  KEY `idx_teacher` (`teacherID`),
  KEY `idx_term_year` (`term`,`academicYear`),
  KEY `idx_day_time` (`dayOfWeek`,`startTime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `userroles`
--

DROP TABLE IF EXISTS `userroles`;
CREATE TABLE IF NOT EXISTS `userroles` (
  `userRoleID` int NOT NULL AUTO_INCREMENT,
  `userID` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `roleID` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `assignedBy` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assignedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`userRoleID`),
  UNIQUE KEY `unique_user_role` (`userID`,`roleID`),
  KEY `idx_user_roles` (`userID`),
  KEY `idx_role_users` (`roleID`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `userroles`
--

INSERT INTO `userroles` (`userRoleID`, `userID`, `roleID`, `assignedBy`, `assignedAt`) VALUES
(5, '1', 'R001', '1', '2026-01-08 12:41:51'),
(6, '3', 'R002', '1', '2026-01-09 06:44:37');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `userID` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `apiToken` varchar(64) DEFAULT NULL,
  `apiTokenExpires` datetime DEFAULT NULL,
  `role` enum('admin','teacher','parent') NOT NULL,
  `firstName` varchar(50) NOT NULL,
  `lastName` varchar(50) NOT NULL,
  `isActive` enum('Y','N') NOT NULL DEFAULT 'Y',
  `mustChangePassword` enum('Y','N') NOT NULL DEFAULT 'N',
  `lastLogin` datetime DEFAULT NULL,
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`userID`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_apiToken` (`apiToken`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`userID`, `username`, `email`, `password`, `apiToken`, `apiTokenExpires`, `role`, `firstName`, `lastName`, `isActive`, `mustChangePassword`, `lastLogin`, `createdAt`, `updatedAt`) VALUES
(1, 'admin', 'admin@lilayipark.edu.zm', '$2y$10$5b8COp/JawmVDQuEH/7zAuv1xPXXE8VgIoJoa1Vsl9YLLGGfdkLHG', NULL, NULL, 'admin', 'System', 'Administrator', 'Y', 'N', '2026-01-21 09:04:08', '2026-01-07 14:32:44', '2026-01-21 09:04:08'),
(3, 'test.teacher', 'test1767@example.com', '$2y$12$OI9fKx08hf1zREw9DSdOReDgM/Ifb6R3c0IMZxdkFQKLnMhMo7PLG', NULL, NULL, 'admin', 'Test', 'Teacher', 'Y', 'N', '2026-01-12 09:14:46', '2026-01-09 08:40:07', '2026-01-12 09:14:46');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`pupilID`) REFERENCES `pupil` (`pupilID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`pupilID`) REFERENCES `pupil` (`pupilID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`pupilID`) REFERENCES `pupil` (`pupilID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_4` FOREIGN KEY (`pupilID`) REFERENCES `pupil` (`pupilID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `borrowrecords`
--
ALTER TABLE `borrowrecords`
  ADD CONSTRAINT `borrowrecords_ibfk_1` FOREIGN KEY (`bookID`) REFERENCES `books` (`bookID`) ON DELETE CASCADE,
  ADD CONSTRAINT `borrowrecords_ibfk_2` FOREIGN KEY (`pupilID`) REFERENCES `pupil` (`pupilID`) ON DELETE CASCADE,
  ADD CONSTRAINT `borrowrecords_ibfk_3` FOREIGN KEY (`issuedBy`) REFERENCES `users` (`userID`),
  ADD CONSTRAINT `borrowrecords_ibfk_4` FOREIGN KEY (`returnedTo`) REFERENCES `users` (`userID`);

--
-- Constraints for table `class`
--
ALTER TABLE `class`
  ADD CONSTRAINT `class_ibfk_1` FOREIGN KEY (`teacherID`) REFERENCES `teacher` (`teacherID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `class_ibfk_2` FOREIGN KEY (`teacherID`) REFERENCES `teacher` (`teacherID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `class_ibfk_3` FOREIGN KEY (`teacherID`) REFERENCES `teacher` (`teacherID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `class_ibfk_4` FOREIGN KEY (`teacherID`) REFERENCES `teacher` (`teacherID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `dailyattendance`
--
ALTER TABLE `dailyattendance`
  ADD CONSTRAINT `dailyattendance_ibfk_1` FOREIGN KEY (`pupilID`) REFERENCES `pupil` (`pupilID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `dailyattendance_ibfk_2` FOREIGN KEY (`markedBy`) REFERENCES `users` (`userID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `examinations`
--
ALTER TABLE `examinations`
  ADD CONSTRAINT `fk_exam_created_by` FOREIGN KEY (`createdBy`) REFERENCES `users` (`userID`) ON DELETE RESTRICT;

--
-- Constraints for table `examschedule`
--
ALTER TABLE `examschedule`
  ADD CONSTRAINT `fk_schedule_exam` FOREIGN KEY (`examID`) REFERENCES `examinations` (`examID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_schedule_invigilator` FOREIGN KEY (`invigilator`) REFERENCES `users` (`userID`) ON DELETE SET NULL;

--
-- Constraints for table `fees`
--
ALTER TABLE `fees`
  ADD CONSTRAINT `fees_ibfk_1` FOREIGN KEY (`classID`) REFERENCES `class` (`classID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fees_ibfk_2` FOREIGN KEY (`classID`) REFERENCES `class` (`classID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fees_ibfk_3` FOREIGN KEY (`classID`) REFERENCES `class` (`classID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fees_ibfk_4` FOREIGN KEY (`classID`) REFERENCES `class` (`classID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `grades`
--
ALTER TABLE `grades`
  ADD CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`pupilID`) REFERENCES `pupil` (`pupilID`) ON DELETE CASCADE,
  ADD CONSTRAINT `grades_ibfk_2` FOREIGN KEY (`classID`) REFERENCES `class` (`classID`) ON DELETE CASCADE,
  ADD CONSTRAINT `grades_ibfk_3` FOREIGN KEY (`recordedBy`) REFERENCES `users` (`userID`) ON DELETE SET NULL;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `fk_payment_fees` FOREIGN KEY (`feeID`) REFERENCES `fees` (`feeID`) ON DELETE SET NULL,
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`pupilID`) REFERENCES `pupil` (`pupilID`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `payment_ibfk_2` FOREIGN KEY (`classID`) REFERENCES `class` (`classID`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `payment_ibfk_3` FOREIGN KEY (`pupilID`) REFERENCES `pupil` (`pupilID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `payment_ibfk_4` FOREIGN KEY (`classID`) REFERENCES `class` (`classID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `payment_ibfk_5` FOREIGN KEY (`pupilID`) REFERENCES `pupil` (`pupilID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `payment_ibfk_6` FOREIGN KEY (`classID`) REFERENCES `class` (`classID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `payment_ibfk_7` FOREIGN KEY (`pupilID`) REFERENCES `pupil` (`pupilID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `payment_ibfk_8` FOREIGN KEY (`classID`) REFERENCES `class` (`classID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pupil`
--
ALTER TABLE `pupil`
  ADD CONSTRAINT `pupil_ibfk_1` FOREIGN KEY (`parentID`) REFERENCES `parent` (`parentID`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `pupil_ibfk_2` FOREIGN KEY (`parentID`) REFERENCES `parent` (`parentID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `pupil_ibfk_3` FOREIGN KEY (`parentID`) REFERENCES `parent` (`parentID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `pupil_ibfk_4` FOREIGN KEY (`parentID`) REFERENCES `parent` (`parentID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pupil_class`
--
ALTER TABLE `pupil_class`
  ADD CONSTRAINT `pupil_class_ibfk_1` FOREIGN KEY (`pupilID`) REFERENCES `pupil` (`pupilID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `pupil_class_ibfk_2` FOREIGN KEY (`classID`) REFERENCES `class` (`classID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reportcomments`
--
ALTER TABLE `reportcomments`
  ADD CONSTRAINT `reportcomments_ibfk_1` FOREIGN KEY (`pupilID`) REFERENCES `pupil` (`pupilID`) ON DELETE CASCADE,
  ADD CONSTRAINT `reportcomments_ibfk_2` FOREIGN KEY (`classID`) REFERENCES `class` (`classID`) ON DELETE CASCADE,
  ADD CONSTRAINT `reportcomments_ibfk_3` FOREIGN KEY (`createdBy`) REFERENCES `users` (`userID`) ON DELETE SET NULL,
  ADD CONSTRAINT `reportcomments_ibfk_4` FOREIGN KEY (`updatedBy`) REFERENCES `users` (`userID`) ON DELETE SET NULL;

--
-- Constraints for table `timetable`
--
ALTER TABLE `timetable`
  ADD CONSTRAINT `timetable_ibfk_1` FOREIGN KEY (`classID`) REFERENCES `class` (`classID`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_ibfk_2` FOREIGN KEY (`teacherID`) REFERENCES `teacher` (`teacherID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
