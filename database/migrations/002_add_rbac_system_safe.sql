-- Safe Migration: Add RBAC System (checks for existing objects)
-- Date: 2026-01-08

-- Step 1: Add userID columns if they don't exist
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'lilayiparkschool' AND TABLE_NAME = 'Teacher' AND COLUMN_NAME = 'userID';
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE Teacher ADD COLUMN userID VARCHAR(10) NULL AFTER teacherID, ADD INDEX idx_teacher_user (userID)', 
    'SELECT "Teacher.userID already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'lilayiparkschool' AND TABLE_NAME = 'Parent' AND COLUMN_NAME = 'userID';
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE Parent ADD COLUMN userID VARCHAR(10) NULL AFTER parentID, ADD INDEX idx_parent_user (userID)', 
    'SELECT "Parent.userID already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 2: Create Roles table
CREATE TABLE IF NOT EXISTS Roles (
    roleID VARCHAR(10) PRIMARY KEY,
    roleName VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 3: Insert default roles
INSERT IGNORE INTO Roles (roleID, roleName, description) VALUES
('R001', 'admin', 'System Administrator - Full access to all modules and settings'),
('R002', 'teacher', 'Teacher - Access to teaching modules, grades, and class management'),
('R003', 'parent', 'Parent - Access to child information, grades, and communications'),
('R004', 'accountant', 'Accountant - Manage school finances and fee payments'),
('R005', 'librarian', 'Librarian - Manage library books and resources');

-- Step 4: Create UserRoles table
CREATE TABLE IF NOT EXISTS UserRoles (
    userRoleID INT PRIMARY KEY AUTO_INCREMENT,
    userID VARCHAR(10) NOT NULL,
    roleID VARCHAR(10) NOT NULL,
    assignedBy VARCHAR(10),
    assignedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_role (userID, roleID),
    INDEX idx_user_roles (userID),
    INDEX idx_role_users (roleID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 5: Create Permissions table
CREATE TABLE IF NOT EXISTS Permissions (
    permissionID VARCHAR(10) PRIMARY KEY,
    permissionName VARCHAR(100) NOT NULL UNIQUE,
    module VARCHAR(50),
    action VARCHAR(50),
    description TEXT,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 6: Create RolePermissions table
CREATE TABLE IF NOT EXISTS RolePermissions (
    rolePermissionID INT PRIMARY KEY AUTO_INCREMENT,
    roleID VARCHAR(10) NOT NULL,
    permissionID VARCHAR(10) NOT NULL,
    UNIQUE KEY unique_role_permission (roleID, permissionID),
    INDEX idx_role_permissions (roleID),
    INDEX idx_permission_roles (permissionID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 7: Insert sample permissions
INSERT IGNORE INTO Permissions (permissionID, permissionName, module, action, description) VALUES
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

-- Step 8: Assign permissions to roles (admin gets all)
INSERT IGNORE INTO RolePermissions (roleID, permissionID)
SELECT 'R001', permissionID FROM Permissions;

-- Teacher permissions
INSERT IGNORE INTO RolePermissions (roleID, permissionID) VALUES
('R002', 'P003'), ('R002', 'P007'), ('R002', 'P009'), 
('R002', 'P010'), ('R002', 'P011'), ('R002', 'P015');

-- Parent permissions
INSERT IGNORE INTO RolePermissions (roleID, permissionID) VALUES
('R003', 'P007'), ('R003', 'P009'), ('R003', 'P013'), ('R003', 'P015');

-- Accountant permissions
INSERT IGNORE INTO RolePermissions (roleID, permissionID) VALUES
('R004', 'P007'), ('R004', 'P013'), ('R004', 'P014');

-- Librarian permissions
INSERT IGNORE INTO RolePermissions (roleID, permissionID) VALUES
('R005', 'P015'), ('R005', 'P016');

-- Step 9: Create default admin user
INSERT IGNORE INTO Users (userID, username, email, password, isActive)
VALUES ('U001', 'admin', 'admin@lilayipark.zm', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- Step 10: Assign admin role
INSERT IGNORE INTO UserRoles (userID, roleID)
VALUES ('U001', 'R001');

-- Step 11: Add foreign key constraints if they don't exist
SET @fk_exists = 0;
SELECT COUNT(*) INTO @fk_exists FROM information_schema.TABLE_CONSTRAINTS 
WHERE CONSTRAINT_SCHEMA = 'lilayiparkschool' AND TABLE_NAME = 'UserRoles' AND CONSTRAINT_NAME = 'userroles_ibfk_1';
SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE UserRoles ADD CONSTRAINT userroles_ibfk_1 FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE CASCADE', 
    'SELECT "UserRoles FK to Users already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists = 0;
SELECT COUNT(*) INTO @fk_exists FROM information_schema.TABLE_CONSTRAINTS 
WHERE CONSTRAINT_SCHEMA = 'lilayiparkschool' AND TABLE_NAME = 'UserRoles' AND CONSTRAINT_NAME = 'userroles_ibfk_2';
SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE UserRoles ADD CONSTRAINT userroles_ibfk_2 FOREIGN KEY (roleID) REFERENCES Roles(roleID) ON DELETE CASCADE', 
    'SELECT "UserRoles FK to Roles already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists = 0;
SELECT COUNT(*) INTO @fk_exists FROM information_schema.TABLE_CONSTRAINTS 
WHERE CONSTRAINT_SCHEMA = 'lilayiparkschool' AND TABLE_NAME = 'UserRoles' AND CONSTRAINT_NAME = 'userroles_ibfk_3';
SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE UserRoles ADD CONSTRAINT userroles_ibfk_3 FOREIGN KEY (assignedBy) REFERENCES Users(userID) ON DELETE SET NULL', 
    'SELECT "UserRoles FK to assignedBy already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists = 0;
SELECT COUNT(*) INTO @fk_exists FROM information_schema.TABLE_CONSTRAINTS 
WHERE CONSTRAINT_SCHEMA = 'lilayiparkschool' AND TABLE_NAME = 'RolePermissions' AND CONSTRAINT_NAME = 'rolepermissions_ibfk_1';
SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE RolePermissions ADD CONSTRAINT rolepermissions_ibfk_1 FOREIGN KEY (roleID) REFERENCES Roles(roleID) ON DELETE CASCADE', 
    'SELECT "RolePermissions FK to Roles already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists = 0;
SELECT COUNT(*) INTO @fk_exists FROM information_schema.TABLE_CONSTRAINTS 
WHERE CONSTRAINT_SCHEMA = 'lilayiparkschool' AND TABLE_NAME = 'RolePermissions' AND CONSTRAINT_NAME = 'rolepermissions_ibfk_2';
SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE RolePermissions ADD CONSTRAINT rolepermissions_ibfk_2 FOREIGN KEY (permissionID) REFERENCES Permissions(permissionID) ON DELETE CASCADE', 
    'SELECT "RolePermissions FK to Permissions already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists = 0;
SELECT COUNT(*) INTO @fk_exists FROM information_schema.TABLE_CONSTRAINTS 
WHERE CONSTRAINT_SCHEMA = 'lilayiparkschool' AND TABLE_NAME = 'Teacher' AND CONSTRAINT_NAME = 'fk_teacher_user';
SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE Teacher ADD CONSTRAINT fk_teacher_user FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE SET NULL', 
    'SELECT "Teacher FK to Users already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists = 0;
SELECT COUNT(*) INTO @fk_exists FROM information_schema.TABLE_CONSTRAINTS 
WHERE CONSTRAINT_SCHEMA = 'lilayiparkschool' AND TABLE_NAME = 'Parent' AND CONSTRAINT_NAME = 'fk_parent_user';
SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE Parent ADD CONSTRAINT fk_parent_user FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE SET NULL', 
    'SELECT "Parent FK to Users already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'RBAC migration completed successfully!' AS Status;
