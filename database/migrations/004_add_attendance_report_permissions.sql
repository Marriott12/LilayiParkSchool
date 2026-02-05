-- Add permissions for attendance and reports
-- Date: 2026-01-08

USE lilayiparkschool;

-- Add new permissions
INSERT IGNORE INTO Permissions (permissionID, permissionName, module, action, description) VALUES
('P017', 'view_attendance', 'attendance', 'read', 'View attendance records'),
('P018', 'manage_attendance', 'attendance', 'all', 'Enter and update attendance'),
('P019', 'view_reports', 'reports', 'read', 'View academic and school reports'),
('P020', 'manage_reports', 'reports', 'all', 'Create and manage reports');

-- Update teacher permissions
-- Teachers can: view classes, view/enter grades, view pupils, view attendance, enter attendance (their class only), view reports
DELETE FROM RolePermissions WHERE roleID = (SELECT roleID FROM Roles WHERE roleName = 'teacher');

INSERT INTO RolePermissions (roleID, permissionID) VALUES
-- View permissions
((SELECT roleID FROM Roles WHERE roleName = 'teacher'), 'P011'), -- view_classes
((SELECT roleID FROM Roles WHERE roleName = 'teacher'), 'P007'), -- view_pupils
((SELECT roleID FROM Roles WHERE roleName = 'teacher'), 'P009'), -- view_grades
((SELECT roleID FROM Roles WHERE roleName = 'teacher'), 'P010'), -- manage_grades (enter results)
((SELECT roleID FROM Roles WHERE roleName = 'teacher'), 'P017'), -- view_attendance
((SELECT roleID FROM Roles WHERE roleName = 'teacher'), 'P018'), -- manage_attendance (enter attendance)
((SELECT roleID FROM Roles WHERE roleName = 'teacher'), 'P019'), -- view_reports
((SELECT roleID FROM Roles WHERE roleName = 'teacher'), 'P020'), -- manage_reports (submit reports)
((SELECT roleID FROM Roles WHERE roleName = 'teacher'), 'P015'), -- view_library
((SELECT roleID FROM Roles WHERE roleName = 'teacher'), 'P003'); -- view_teachers

-- Update parent permissions
-- Parents can: view their child's details, grades, fees, library, attendance, reports
DELETE FROM RolePermissions WHERE roleID = (SELECT roleID FROM Roles WHERE roleName = 'parent');

INSERT INTO RolePermissions (roleID, permissionID) VALUES
((SELECT roleID FROM Roles WHERE roleName = 'parent'), 'P007'), -- view_pupils (their child only)
((SELECT roleID FROM Roles WHERE roleName = 'parent'), 'P009'), -- view_grades (their child only)
((SELECT roleID FROM Roles WHERE roleName = 'parent'), 'P013'), -- view_fees (payment history)
((SELECT roleID FROM Roles WHERE roleName = 'parent'), 'P017'), -- view_attendance (their child only)
((SELECT roleID FROM Roles WHERE roleName = 'parent'), 'P019'), -- view_reports (their child only)
((SELECT roleID FROM Roles WHERE roleName = 'parent'), 'P015'); -- view_library

-- Update admin permissions to include new permissions
INSERT IGNORE INTO RolePermissions (roleID, permissionID) VALUES
((SELECT roleID FROM Roles WHERE roleName = 'admin'), 'P017'), -- view_attendance
((SELECT roleID FROM Roles WHERE roleName = 'admin'), 'P018'), -- manage_attendance
((SELECT roleID FROM Roles WHERE roleName = 'admin'), 'P019'), -- view_reports
((SELECT roleID FROM Roles WHERE roleName = 'admin'), 'P020'); -- manage_reports

SELECT 'Permissions updated successfully!' as status;
SELECT r.roleName, p.permissionName, p.description 
FROM RolePermissions rp 
JOIN Roles r ON rp.roleID = r.roleID 
JOIN Permissions p ON rp.permissionID = p.permissionID 
WHERE r.roleName IN ('teacher', 'parent')
ORDER BY r.roleName, p.module;
