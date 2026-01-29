-- List all tables in the current database
SHOW TABLES;

-- For each table that should be uppercase, generate a RENAME statement if needed
-- Example: If you see 'users' but want 'Users', run the following:

USE lilayiparkschool;

-- Only run these if the table exists in lowercase and you want it uppercase
RENAME TABLE `users` TO `Users`;
RENAME TABLE `roles` TO `Roles`;
RENAME TABLE `pupil` TO `Pupil`;
RENAME TABLE `parent` TO `Parent`;
RENAME TABLE `class` TO `Class`;
RENAME TABLE `payment` TO `Payment`;
RENAME TABLE `grades` TO `Grades`;
RENAME TABLE `books` TO `Books`;
RENAME TABLE `borrowrecords` TO `BorrowRecords`;
RENAME TABLE `attendance` TO `Attendance`;
RENAME TABLE `examinations` TO `Examinations`;
RENAME TABLE `examschedule` TO `ExamSchedule`;
RENAME TABLE `classsubjects` TO `ClassSubjects`;
RENAME TABLE `pupil_class` TO `Pupil_Class`;
RENAME TABLE `reportcomments` TO `ReportComments`;
RENAME TABLE `rolepermissions` TO `RolePermissions`;
RENAME TABLE `permissions` TO `Permissions`;

-- Do NOT rename 'holidays' or 'settings' (they should stay lowercase)

-- After running, re-check with SHOW TABLES; to confirm changes.
