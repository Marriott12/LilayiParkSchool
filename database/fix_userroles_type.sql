-- =====================================================
-- CRITICAL FIX: UserRoles userID Type Mismatch
-- Run this on remote server if admin login fails
-- =====================================================

-- This fixes the data type mismatch between:
-- Users.userID (INT) and UserRoles.userID (VARCHAR)

-- Step 1: Backup existing role assignments
CREATE TEMPORARY TABLE IF NOT EXISTS temp_user_roles AS
SELECT * FROM UserRoles;

-- Step 2: Drop foreign keys if they exist
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS=0;

-- Step 3: Recreate UserRoles table with correct structure
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

-- Step 4: Restore foreign key checks
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;

-- Step 5: Restore data (if any valid data existed)
INSERT IGNORE INTO UserRoles (userID, roleID, assignedAt)
SELECT CAST(userID AS UNSIGNED), roleID, assignedAt 
FROM temp_user_roles
WHERE userID REGEXP '^[0-9]+$';

-- Step 6: Drop temporary table
DROP TEMPORARY TABLE IF EXISTS temp_user_roles;

-- Step 7: Ensure admin user has admin role
INSERT INTO UserRoles (userID, roleID)
SELECT u.userID, 'R001'
FROM Users u
WHERE u.username = 'admin'
ON DUPLICATE KEY UPDATE roleID = VALUES(roleID);

-- Step 8: Verify the fix
SELECT 
    u.userID,
    u.username,
    u.email,
    u.isActive,
    r.roleName
FROM Users u
LEFT JOIN UserRoles ur ON u.userID = ur.userID
LEFT JOIN Roles r ON ur.roleID = r.roleID
WHERE u.username = 'admin';

-- Step 9: Check table structure
SHOW CREATE TABLE UserRoles;

SELECT '✅ UserRoles table fixed - userID is now INT' AS Status;

-- =====================================================
-- COMPLETE FIX SCRIPT
-- =====================================================
-- Now also update the admin password

UPDATE Users 
SET password = '$2y$12$gl.WX8Hppgsup4wcrbtVLub768yjANtWmZ9PJDE0v21PU1DRklfa.',
    isActive = 'Y',
    mustChangePassword = 'Y'
WHERE username = 'admin';

SELECT '✅ Admin password updated' AS Status;

-- Final verification
SELECT 
    CASE 
        WHEN EXISTS (
            SELECT 1 FROM Users u
            JOIN UserRoles ur ON u.userID = ur.userID
            JOIN Roles r ON ur.roleID = r.roleID
            WHERE u.username = 'admin' 
            AND u.isActive = 'Y'
            AND r.roleName = 'admin'
        )
        THEN '✅ Admin login should now work! Try: username=admin, password=admin123'
        ELSE '❌ There is still an issue - check the output above'
    END AS Final_Status;
