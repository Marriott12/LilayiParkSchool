-- =====================================================
-- Database Verification Script
-- Run this after deploying schema and seed data
-- =====================================================

-- Check database exists and character set
SELECT 
    SCHEMA_NAME as 'Database',
    DEFAULT_CHARACTER_SET_NAME as 'Character Set',
    DEFAULT_COLLATION_NAME as 'Collation'
FROM information_schema.SCHEMATA 
WHERE SCHEMA_NAME = 'lilayiparkschool';

-- Count all tables (should be 26 tables)
SELECT COUNT(*) as 'Total Tables' 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'lilayiparkschool';

-- List all tables
SELECT TABLE_NAME as 'Table Name', 
       TABLE_ROWS as 'Rows',
       ENGINE as 'Engine',
       TABLE_COLLATION as 'Collation'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'lilayiparkschool'
ORDER BY TABLE_NAME;

-- Verify triggers (should be 5 triggers)
SELECT TRIGGER_NAME as 'Trigger', 
       EVENT_OBJECT_TABLE as 'Table',
       ACTION_TIMING as 'Timing',
       EVENT_MANIPULATION as 'Event'
FROM information_schema.TRIGGERS 
WHERE TRIGGER_SCHEMA = 'lilayiparkschool'
ORDER BY EVENT_OBJECT_TABLE;

-- Check RBAC setup
SELECT 'Roles' as 'Table', COUNT(*) as 'Count' FROM Roles
UNION ALL
SELECT 'Permissions', COUNT(*) FROM Permissions
UNION ALL
SELECT 'RolePermissions', COUNT(*) FROM RolePermissions
UNION ALL
SELECT 'Users', COUNT(*) FROM Users
UNION ALL
SELECT 'UserRoles', COUNT(*) FROM UserRoles;

-- Verify default roles
SELECT roleID, roleName, description 
FROM Roles 
ORDER BY roleID;

-- Count permissions by module
SELECT module, COUNT(*) as 'Permission Count'
FROM Permissions
GROUP BY module
ORDER BY module;

-- Verify admin user and role assignment
SELECT 
    u.username,
    u.email,
    u.firstName,
    u.lastName,
    u.role,
    u.isActive,
    r.roleName as 'Assigned Role'
FROM Users u
LEFT JOIN UserRoles ur ON u.userID = ur.userID
LEFT JOIN Roles r ON ur.roleID = r.roleID
WHERE u.username = 'admin';

-- Check subjects
SELECT subjectCode, subjectName, isActive 
FROM Subjects 
ORDER BY subjectCode;

-- Check grading scale
SELECT grade, minMarks, maxMarks, gradePoint, description 
FROM GradingScale 
ORDER BY minMarks DESC;

-- Verify key settings
SELECT category, settingKey, settingValue
FROM settings
WHERE category IN ('school', 'academic', 'library')
ORDER BY category, settingKey;

-- Check foreign key constraints
SELECT 
    TABLE_NAME as 'Child Table',
    CONSTRAINT_NAME as 'FK Name',
    REFERENCED_TABLE_NAME as 'Parent Table'
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'lilayiparkschool'
  AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME, REFERENCED_TABLE_NAME;

-- Verify indexes
SELECT 
    TABLE_NAME as 'Table',
    INDEX_NAME as 'Index',
    COLUMN_NAME as 'Column',
    NON_UNIQUE as 'Non-Unique'
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = 'lilayiparkschool'
  AND INDEX_NAME != 'PRIMARY'
ORDER BY TABLE_NAME, INDEX_NAME;

-- Check for any errors or warnings
SHOW WARNINGS;
SHOW ERRORS;

-- Final status message
SELECT 
    CASE 
        WHEN (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'lilayiparkschool') >= 26
         AND (SELECT COUNT(*) FROM Roles) = 5
         AND (SELECT COUNT(*) FROM Permissions) >= 20
         AND (SELECT COUNT(*) FROM Users WHERE username = 'admin') = 1
         AND (SELECT COUNT(*) FROM Subjects) >= 7
         AND (SELECT COUNT(*) FROM GradingScale) = 6
         AND (SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = 'lilayiparkschool') = 5
        THEN '✅ DATABASE DEPLOYMENT SUCCESSFUL - All checks passed!'
        ELSE '⚠️ WARNING - Some checks failed. Review output above.'
    END as 'Deployment Status';

-- =====================================================
-- END OF VERIFICATION
-- =====================================================
