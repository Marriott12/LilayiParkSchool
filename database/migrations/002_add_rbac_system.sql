-- Migration: Add Role-Based Access Control (RBAC) System
-- Date: 2026-01-08
-- Description: Adds user account linking to Teachers/Parents and implements roles/permissions

-- Step 1: Add userID columns to Teacher and Parent tables
ALTER TABLE Teacher 
    ADD COLUMN userID VARCHAR(10) NULL AFTER teacherID,
    ADD INDEX idx_teacher_user (userID);

ALTER TABLE Parent 
    ADD COLUMN userID VARCHAR(10) NULL AFTER parentID,
    ADD INDEX idx_parent_user (userID);

-- Step 2: Create Roles table
CREATE TABLE IF NOT EXISTS Roles (
    roleID VARCHAR(10) PRIMARY KEY,
    roleName VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 3: Create trigger for Role ID generation
DELIMITER $$
CREATE TRIGGER before_role_insert
BEFORE INSERT ON Roles
FOR EACH ROW
BEGIN
    IF NEW.roleID IS NULL OR NEW.roleID = '' THEN
        DECLARE next_id INT;
        DECLARE new_role_id VARCHAR(10);
        
        SELECT IFNULL(MAX(CAST(SUBSTRING(roleID, 2) AS UNSIGNED)), 0) + 1 INTO next_id
        FROM Roles;
        
        SET new_role_id = CONCAT('R', LPAD(next_id, 3, '0'));
        SET NEW.roleID = new_role_id;
    END IF;
END$$
DELIMITER ;

-- Step 4: Insert default roles
INSERT INTO Roles (roleID, roleName, description) VALUES
('R001', 'admin', 'System Administrator - Full access to all modules and settings'),
('R002', 'teacher', 'Teacher - Access to teaching modules, grades, and class management'),
('R003', 'parent', 'Parent - Access to child information, grades, and communications'),
('R004', 'accountant', 'Accountant - Manage school finances and fee payments'),
('R005', 'librarian', 'Librarian - Manage library books and resources');

-- Step 5: Create UserRoles junction table (many-to-many)
CREATE TABLE IF NOT EXISTS UserRoles (
    userRoleID INT PRIMARY KEY AUTO_INCREMENT,
    userID VARCHAR(10) NOT NULL,
    roleID VARCHAR(10) NOT NULL,
    assignedBy VARCHAR(10),
    assignedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE CASCADE,
    FOREIGN KEY (roleID) REFERENCES Roles(roleID) ON DELETE CASCADE,
    FOREIGN KEY (assignedBy) REFERENCES Users(userID) ON DELETE SET NULL,
    UNIQUE KEY unique_user_role (userID, roleID),
    INDEX idx_user_roles (userID),
    INDEX idx_role_users (roleID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 6: Create Permissions table for granular access control
CREATE TABLE IF NOT EXISTS Permissions (
    permissionID VARCHAR(10) PRIMARY KEY,
    permissionName VARCHAR(100) NOT NULL UNIQUE,
    module VARCHAR(50),
    action VARCHAR(50),
    description TEXT,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 7: Create trigger for Permission ID generation
DELIMITER $$
CREATE TRIGGER before_permission_insert
BEFORE INSERT ON Permissions
FOR EACH ROW
BEGIN
    IF NEW.permissionID IS NULL OR NEW.permissionID = '' THEN
        DECLARE next_id INT;
        DECLARE new_perm_id VARCHAR(10);
        
        SELECT IFNULL(MAX(CAST(SUBSTRING(permissionID, 2) AS UNSIGNED)), 0) + 1 INTO next_id
        FROM Permissions;
        
        SET new_perm_id = CONCAT('P', LPAD(next_id, 3, '0'));
        SET NEW.permissionID = new_perm_id;
    END IF;
END$$
DELIMITER ;

-- Step 8: Create RolePermissions junction table
CREATE TABLE IF NOT EXISTS RolePermissions (
    rolePermissionID INT PRIMARY KEY AUTO_INCREMENT,
    roleID VARCHAR(10) NOT NULL,
    permissionID VARCHAR(10) NOT NULL,
    FOREIGN KEY (roleID) REFERENCES Roles(roleID) ON DELETE CASCADE,
    FOREIGN KEY (permissionID) REFERENCES Permissions(permissionID) ON DELETE CASCADE,
    UNIQUE KEY unique_role_permission (roleID, permissionID),
    INDEX idx_role_permissions (roleID),
    INDEX idx_permission_roles (permissionID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 9: Add foreign key constraints for userID columns
ALTER TABLE Teacher 
    ADD CONSTRAINT fk_teacher_user 
    FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE SET NULL;

ALTER TABLE Parent 
    ADD CONSTRAINT fk_parent_user 
    FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE SET NULL;

-- Step 10: Insert sample permissions
INSERT INTO Permissions (permissionID, permissionName, module, action, description) VALUES
('P001', 'manage_users', 'users', 'all', 'Create, read, update, delete user accounts'),
('P002', 'manage_roles', 'roles', 'all', 'Assign and revoke user roles'),
('P003', 'view_teachers', 'teachers', 'read', 'View teacher information'),
('P004', 'manage_teachers', 'teachers', 'all', 'Create, update, delete teachers'),
('P005', 'view_parents', 'parents', 'read', 'View parent information'),
('P006', 'manage_parents', 'parents', 'all', 'Create, update, delete parents'),
('P007', 'view_pupils', 'pupils', 'read', 'View pupil information'),
('P008', 'manage_pupils', 'pupils', 'all', 'Create, update, delete pupils'),
('P009', 'view_grades', 'grades', 'read', 'View student grades'),
('P010', 'manage_grades', 'grades', 'all', 'Enter and update student grades'),
('P011', 'view_classes', 'classes', 'read', 'View class information'),
('P012', 'manage_classes', 'classes', 'all', 'Create, update class assignments'),
('P013', 'view_fees', 'fees', 'read', 'View fee information'),
('P014', 'manage_fees', 'fees', 'all', 'Manage fee payments and records'),
('P015', 'view_library', 'library', 'read', 'View library resources'),
('P016', 'manage_library', 'library', 'all', 'Manage library books and lending');

-- Step 11: Assign permissions to roles
-- Admin gets all permissions
INSERT INTO RolePermissions (roleID, permissionID)
SELECT 'R001', permissionID FROM Permissions;

-- Teacher permissions
INSERT INTO RolePermissions (roleID, permissionID) VALUES
('R002', 'P003'), -- view_teachers
('R002', 'P007'), -- view_pupils
('R002', 'P009'), -- view_grades
('R002', 'P010'), -- manage_grades
('R002', 'P011'), -- view_classes
('R002', 'P015'); -- view_library

-- Parent permissions
INSERT INTO RolePermissions (roleID, permissionID) VALUES
('R003', 'P007'), -- view_pupils (own children only)
('R003', 'P009'), -- view_grades (own children only)
('R003', 'P013'), -- view_fees (own children only)
('R003', 'P015'); -- view_library

-- Accountant permissions
INSERT INTO RolePermissions (roleID, permissionID) VALUES
('R004', 'P007'), -- view_pupils
('R004', 'P013'), -- view_fees
('R004', 'P014'); -- manage_fees

-- Librarian permissions
INSERT INTO RolePermissions (roleID, permissionID) VALUES
('R005', 'P015'), -- view_library
('R005', 'P016'); -- manage_library

-- Step 12: Create a default admin user if Users table has no admin
-- Note: Password is 'admin123' hashed with PASSWORD_DEFAULT
INSERT INTO Users (userID, username, email, password, isActive)
SELECT 'U001', 'admin', 'admin@lilayipark.zm', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1
WHERE NOT EXISTS (SELECT 1 FROM Users WHERE username = 'admin');

-- Assign admin role to default admin user
INSERT INTO UserRoles (userID, roleID)
SELECT 'U001', 'R001'
WHERE NOT EXISTS (SELECT 1 FROM UserRoles WHERE userID = 'U001' AND roleID = 'R001');

-- Migration completed successfully
