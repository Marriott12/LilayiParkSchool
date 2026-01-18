-- =====================================================
-- Fix Admin User Login Issue
-- Run this on the remote server if admin login fails
-- =====================================================

-- This script will:
-- 1. Update the admin user password to a working hash
-- 2. Ensure the user is active
-- 3. Verify the admin role is assigned

-- Method 1: Update with a fresh password hash
-- Password: admin123
UPDATE Users 
SET password = '$2y$12$gl.WX8Hppgsup4wcrbtVLub768yjANtWmZ9PJDE0v21PU1DRklfa.',
    isActive = 'Y',
    mustChangePassword = 'Y'
WHERE username = 'admin';

-- Verify the update
SELECT userID, username, email, role, isActive, 
       CASE WHEN password IS NOT NULL THEN 'Password Set' ELSE 'No Password' END as password_status
FROM Users 
WHERE username = 'admin';

-- Ensure admin has the admin role assigned
INSERT INTO UserRoles (userID, roleID)
SELECT u.userID, 'R001'
FROM Users u
WHERE u.username = 'admin'
AND NOT EXISTS (
    SELECT 1 FROM UserRoles ur 
    WHERE ur.userID = u.userID AND ur.roleID = 'R001'
);

-- Verify role assignment
SELECT u.username, u.email, r.roleName
FROM Users u
LEFT JOIN UserRoles ur ON u.userID = ur.userID
LEFT JOIN Roles r ON ur.roleID = r.roleID
WHERE u.username = 'admin';

-- =====================================================
-- Alternative: Create admin user if it doesn't exist
-- =====================================================

INSERT INTO Users (username, email, password, role, firstName, lastName, isActive, mustChangePassword)
SELECT 'admin', 'admin@lilayipark.edu.zm', 
       '$2y$12$gl.WX8Hppgsup4wcrbtVLub768yjANtWmZ9PJDE0v21PU1DRklfa.',
       'admin', 'System', 'Administrator', 'Y', 'Y'
WHERE NOT EXISTS (SELECT 1 FROM Users WHERE username = 'admin');

-- Assign role if user was just created
INSERT INTO UserRoles (userID, roleID)
SELECT u.userID, 'R001'
FROM Users u
WHERE u.username = 'admin'
AND NOT EXISTS (
    SELECT 1 FROM UserRoles ur 
    WHERE ur.userID = u.userID AND ur.roleID = 'R001'
);

-- =====================================================
-- Final Verification
-- =====================================================

SELECT 
    'Admin User Check' as Check_Type,
    CASE 
        WHEN EXISTS (SELECT 1 FROM Users WHERE username = 'admin' AND isActive = 'Y')
        THEN '✅ Admin user exists and is active'
        ELSE '❌ Admin user missing or inactive'
    END as Status;

SELECT 
    'Admin Role Check' as Check_Type,
    CASE 
        WHEN EXISTS (
            SELECT 1 FROM Users u
            JOIN UserRoles ur ON u.userID = ur.userID
            WHERE u.username = 'admin' AND ur.roleID = 'R001'
        )
        THEN '✅ Admin role assigned'
        ELSE '❌ Admin role not assigned'
    END as Status;

-- Show the admin user details
SELECT 
    userID,
    username,
    email,
    role as user_role,
    firstName,
    lastName,
    isActive,
    mustChangePassword,
    lastLogin,
    SUBSTRING(password, 1, 20) as password_hash_preview
FROM Users 
WHERE username = 'admin';

-- =====================================================
-- INSTRUCTIONS
-- =====================================================
-- After running this script:
-- 1. Try logging in with username: admin, password: admin123
-- 2. You will be prompted to change the password
-- 3. If it still doesn't work, check the output above
-- =====================================================
