-- Migration: Create Pupil_Transfer table
-- Date: 2026-02-25

CREATE TABLE IF NOT EXISTS Pupil_Transfer (
    transferID INT AUTO_INCREMENT PRIMARY KEY,
    pupilID VARCHAR(10) NOT NULL,
    fromClassID VARCHAR(10) DEFAULT NULL,
    toSchool VARCHAR(150) DEFAULT NULL,
    toClassID VARCHAR(10) DEFAULT NULL,
    transferDate DATE NOT NULL,
    reason TEXT,
    notes TEXT,
    outstanding DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pupilID) REFERENCES Pupil(pupilID) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_pt_pupil (pupilID),
    INDEX idx_pt_transferDate (transferDate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
