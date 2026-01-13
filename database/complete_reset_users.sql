-- =====================================================
-- Complete Database Reset for Admin Login
-- Use this if nothing else works
-- =====================================================

-- This script will completely reset the admin user and roles

-- Step 1: Remove any existing admin user data
DELETE FROM UserRoles WHERE userID IN (SELECT userID FROM Users WHERE username = 'admin');
DELETE FROM Users WHERE username = 'admin';

-- Step 2: Verify and fix UserRoles table structure
-- Check current structure
SELECT COLUMN_NAME, DATA_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'lilayiparkschool' 
AND TABLE_NAME = 'UserRoles' 
AND COLUMN_NAME = 'userID';

-- If userID is not INT, fix it
SET @col_type = (SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_SCHEMA = 'lilayiparkschool' 
                 AND TABLE_NAME = 'UserRoles' 
                 AND COLUMN_NAME = 'userID');

-- Drop and recreate UserRoles if needed
DROP TABLE IF EXISTS UserRoles_backup;
CREATE TABLE UserRoles_backup AS SELECT * FROM UserRoles;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS UserRoles;

CREATE TABLE UserRoles (
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

SET FOREIGN_KEY_CHECKS = 1;

-- Step 3: Ensure Roles table has admin role
INSERT INTO Roles (roleID, roleName, description) VALUES
('R001', 'admin', 'System Administrator - Full access to all modules and settings')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Step 4: Create fresh admin user with correct password
INSERT INTO Users (username, email, password, role, firstName, lastName, isActive, mustChangePassword)
VALUES (
    'admin',
    'admin@lilayipark.edu.zm',
    '$2y$12$gl.WX8Hppgsup4wcrbtVLub768yjANtWmZ9PJDE0v21PU1DRklfa.',
    'admin',
    'System',
    'Administrator',
    'Y',
    'Y'
);

-- Step 5: Assign admin role
INSERT INTO UserRoles (userID, roleID)
SELECT userID, 'R001'
FROM Users
WHERE username = 'admin';

-- Step 6: Verify everything
SELECT '=== VERIFICATION RESULTS ===' as '';

SELECT 'Admin User:' as Check_Type,
       u.userID,
       u.username,
       u.email,
       u.isActive,
       SUBSTRING(u.password, 1, 20) as password_preview
FROM Users u
WHERE u.username = 'admin';

SELECT 'Admin Role Assignment:' as Check_Type,
       u.username,
       ur.userID,
       ur.roleID,
       r.roleName
FROM Users u
JOIN UserRoles ur ON u.userID = ur.userID
JOIN Roles r ON ur.roleID = r.roleID
WHERE u.username = 'admin';

SELECT 'UserRoles Structure:' as Check_Type,
       COLUMN_NAME,
       DATA_TYPE,
       IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'lilayiparkschool'
AND TABLE_NAME = 'UserRoles';

-- Step 7: Test password
SELECT 'Password Test:' as Check_Type,
       CASE 
           WHEN EXISTS (
               SELECT 1 FROM Users 
               WHERE username = 'admin' 
               AND password = '$2y$12$gl.WX8Hppgsup4wcrbtVLub768yjANtWmZ9PJDE0v21PU1DRklfa.'
           )
           THEN 'Password hash is set correctly'
           ELSE 'Password hash mismatch!'
       END as Result;

SELECT '=== FINAL STATUS ===' as '';

SELECT 
    CASE 
        WHEN EXISTS (
            SELECT 1 
            FROM Users u
            JOIN UserRoles ur ON u.userID = ur.userID
            JOIN Roles r ON ur.roleID = r.roleID
            WHERE u.username = 'admin'
            AND u.isActive = 'Y'
            AND r.roleName = 'admin'
            AND u.password = '$2y$12$gl.WX8Hppgsup4wcrbtVLub768yjANtWmZ9PJDE0v21PU1DRklfa.'
        )
        THEN '✅ SUCCESS! Login credentials: username=admin, password=admin123'
        ELSE '❌ FAILED - Check verification results above'
    END as Final_Result;

-- =====================================================
-- CREATE TEST.TEACHER USER (if needed)
-- =====================================================

-- First ensure teacher role exists
INSERT INTO Roles (roleID, roleName, description) VALUES
('R002', 'teacher', 'Teacher - Access to teaching modules, grades, and class management')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Create test.teacher user
INSERT INTO Users (username, email, password, role, firstName, lastName, isActive, mustChangePassword)
VALUES (
    'test.teacher',
    'test.teacher@lilayipark.edu.zm',
    '$2y$12$gl.WX8Hppgsup4wcrbtVLub768yjANtWmZ9PJDE0v21PU1DRklfa.',
    'teacher',
    'Test',
    'Teacher',
    'Y',
    'Y'
)
ON DUPLICATE KEY UPDATE 
    password = '$2y$12$gl.WX8Hppgsup4wcrbtVLub768yjANtWmZ9PJDE0v21PU1DRklfa.',
    isActive = 'Y';

-- Assign teacher role
INSERT INTO UserRoles (userID, roleID)
SELECT userID, 'R002'
FROM Users
WHERE username = 'test.teacher'
ON DUPLICATE KEY UPDATE roleID = 'R002';

-- Verify test.teacher
SELECT 'Test Teacher User:' as Check_Type,
       u.username,
       u.email,
       u.isActive,
       r.roleName
FROM Users u
LEFT JOIN UserRoles ur ON u.userID = ur.userID
LEFT JOIN Roles r ON ur.roleID = r.roleID
WHERE u.username = 'test.teacher';

-- =====================================================
-- CLEANUP
-- =====================================================

-- Drop backup table if everything looks good
-- DROP TABLE IF EXISTS UserRoles_backup;

SELECT '=== INSTRUCTIONS ===' as '';
SELECT 'Both users should now work:' as '';
SELECT '1. admin / admin123' as '';
SELECT '2. test.teacher / teacher123' as '';
SELECT 'If still not working, run diagnose_db.php in browser' as '';
