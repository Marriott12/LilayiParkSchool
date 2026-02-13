-- Simple RBAC setup
CREATE TABLE IF NOT EXISTS Roles (
    roleID VARCHAR(10) PRIMARY KEY,
    roleName VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS UserRoles (
    userRoleID INT PRIMARY KEY AUTO_INCREMENT,
    userID VARCHAR(10) NOT NULL,
    roleID VARCHAR(10) NOT NULL,
    assignedBy VARCHAR(10),
    assignedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_role (userID, roleID)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Permissions (
    permissionID VARCHAR(10) PRIMARY KEY,
    permissionName VARCHAR(100) NOT NULL UNIQUE,
    module VARCHAR(50),
    action VARCHAR(50),
    description TEXT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS RolePermissions (
    rolePermissionID INT PRIMARY KEY AUTO_INCREMENT,
    roleID VARCHAR(10) NOT NULL,
    permissionID VARCHAR(10) NOT NULL,
    UNIQUE KEY unique_role_permission (roleID, permissionID)
) ENGINE=InnoDB;

INSERT IGNORE INTO Roles (roleID, roleName, description) VALUES
('R001', 'admin', 'System Administrator'),
('R002', 'teacher', 'Teacher'),
('R003', 'parent', 'Parent'),
('R004', 'accountant', 'Accountant'),
('R005', 'librarian', 'Librarian');

INSERT IGNORE INTO Permissions (permissionID, permissionName, module, action, description) VALUES
('P001', 'manage_users', 'users', 'all', 'Manage users'),
('P002', 'manage_roles', 'roles', 'all', 'Manage roles'),
('P003', 'view_teachers', 'teachers', 'read', 'View teachers'),
('P004', 'manage_teachers', 'teachers', 'all', 'Manage teachers'),
('P009', 'view_grades', 'grades', 'read', 'View grades'),
('P010', 'manage_grades', 'grades', 'all', 'Manage grades');

INSERT IGNORE INTO RolePermissions (roleID, permissionID) VALUES
('R001', 'P001'), ('R001', 'P002'), ('R001', 'P003'), ('R001', 'P004'),
('R002', 'P003'), ('R002', 'P009'), ('R002', 'P010');

UPDATE Users SET isActive = 1 WHERE username = 'admin';
INSERT IGNORE INTO UserRoles (userID, roleID) 
SELECT userID, 'R001' FROM Users WHERE username = 'admin';

SELECT 'RBAC setup complete!' AS Result;
