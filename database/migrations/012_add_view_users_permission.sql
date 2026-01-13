-- Add missing view_users permission
-- This permission allows users to view the users list and user details

INSERT IGNORE INTO Permissions (permissionID, permissionName, module, action, description) VALUES
('P017', 'view_users', 'users', 'read', 'View user accounts and details');

-- Assign view_users permission to admin role
INSERT IGNORE INTO RolePermissions (roleID, permissionID) VALUES
('R001', 'P017');
