-- =====================================================
-- Check Actual Table Names in Database
-- Run this to see what tables exist and their exact case
-- =====================================================

-- Show all tables in current database
SHOW TABLES;

-- Get exact table names with case
SELECT TABLE_NAME 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME;

-- If you see lowercase tables (users, roles, etc.) instead of 
-- capitalized (Users, Roles, etc.), you have a case-sensitivity issue.

-- =====================================================
-- SOLUTION: Rename tables to match application code
-- =====================================================

-- Only run these if your tables are lowercase:
-- Note: holidays and settings should stay lowercase (they're correct)

RENAME TABLE `users` TO `Users`;
RENAME TABLE `roles` TO `Roles`;
RENAME TABLE `permissions` TO `Permissions`;
RENAME TABLE `userroles` TO `UserRoles`;
RENAME TABLE `rolepermissions` TO `RolePermissions`;
RENAME TABLE `teacher` TO `Teacher`;
RENAME TABLE `parent` TO `Parent`;
RENAME TABLE `pupil` TO `Pupil`;
RENAME TABLE `class` TO `Class`;
RENAME TABLE `pupil_class` TO `Pupil_Class`;
RENAME TABLE `fees` TO `Fees`;
RENAME TABLE `payment` TO `Payment`;
RENAME TABLE `attendance` TO `Attendance`;
RENAME TABLE `dailyattendance` TO `DailyAttendance`;
RENAME TABLE `subjects` TO `Subjects`;
RENAME TABLE `grades` TO `Grades`;
RENAME TABLE `examinations` TO `Examinations`;
RENAME TABLE `examschedule` TO `ExamSchedule`;
RENAME TABLE `timetable` TO `Timetable`;
RENAME TABLE `reportcomments` TO `ReportComments`;
RENAME TABLE `books` TO `Books`;
RENAME TABLE `borrowrecords` TO `BorrowRecords`;
RENAME TABLE `announcements` TO `Announcements`;

-- Check if classsubjects exists (not in original schema but shows in your database)
RENAME TABLE IF EXISTS `classsubjects` TO `ClassSubjects`;

-- Verify the changes
SHOW TABLES;

SELECT '✅ Table names have been corrected to match application code' AS Status;
