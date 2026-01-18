-- Library Management System Migration
-- Creates tables for library book management and borrowing

-- Books Table
CREATE TABLE IF NOT EXISTS Books (
    bookID INT PRIMARY KEY AUTO_INCREMENT,
    ISBN VARCHAR(20) UNIQUE NULL,
    title VARCHAR(200) NOT NULL,
    author VARCHAR(200) NOT NULL,
    publisher VARCHAR(200) NULL,
    publicationYear YEAR NULL,
    category VARCHAR(100) NOT NULL,
    totalCopies INT NOT NULL DEFAULT 1,
    availableCopies INT NOT NULL DEFAULT 1,
    shelfLocation VARCHAR(50) NULL,
    description TEXT NULL,
    coverImage VARCHAR(255) NULL,
    isActive BOOLEAN DEFAULT TRUE,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_title (title),
    INDEX idx_author (author),
    INDEX idx_category (category),
    INDEX idx_isbn (ISBN),
    INDEX idx_active (isActive)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Borrow Records Table
CREATE TABLE IF NOT EXISTS BorrowRecords (
    borrowID INT PRIMARY KEY AUTO_INCREMENT,
    bookID INT NOT NULL,
    pupilID VARCHAR(10) NOT NULL,
    borrowDate DATE NOT NULL,
    dueDate DATE NOT NULL,
    returnDate DATE NULL,
    status ENUM('borrowed', 'returned', 'overdue', 'lost') NOT NULL DEFAULT 'borrowed',
    fine DECIMAL(10,2) DEFAULT 0.00,
    remarks TEXT NULL,
    issuedBy INT NOT NULL,
    returnedTo INT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (bookID) REFERENCES Books(bookID) ON DELETE CASCADE,
    FOREIGN KEY (pupilID) REFERENCES Pupil(pupilID) ON DELETE CASCADE,
    FOREIGN KEY (issuedBy) REFERENCES Users(userID),
    FOREIGN KEY (returnedTo) REFERENCES Users(userID),
    
    INDEX idx_pupil (pupilID),
    INDEX idx_book (bookID),
    INDEX idx_status (status),
    INDEX idx_dates (borrowDate, dueDate, returnDate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Library Settings
INSERT INTO Settings (settingKey, settingValue, category) VALUES
('library_borrow_days', '14', 'library'),
('library_fine_per_day', '0.50', 'library'),
('library_max_books', '3', 'library')
ON DUPLICATE KEY UPDATE settingValue = VALUES(settingValue);

-- Library Permissions
INSERT INTO Permissions (permissionID, permissionName, module, action, description) VALUES
('P021', 'view_library', 'library', 'view', 'View library and books'),
('P022', 'manage_library', 'library', 'manage', 'Manage books and borrow records')
ON DUPLICATE KEY UPDATE permissionName = VALUES(permissionName);

-- Grant library permissions to admin
INSERT INTO RolePermissions (roleID, permissionID)
SELECT r.roleID, 'P021'
FROM Roles r
WHERE r.roleName = 'admin'
ON DUPLICATE KEY UPDATE permissionID = VALUES(permissionID);

INSERT INTO RolePermissions (roleID, permissionID)
SELECT r.roleID, 'P022'
FROM Roles r
WHERE r.roleName = 'admin'
ON DUPLICATE KEY UPDATE permissionID = VALUES(permissionID);

-- Grant view permission to teachers
INSERT INTO RolePermissions (roleID, permissionID)
SELECT r.roleID, 'P021'
FROM Roles r
WHERE r.roleName = 'teacher'
ON DUPLICATE KEY UPDATE permissionID = VALUES(permissionID);
