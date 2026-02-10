-- Migration: Add fee tracking columns to Payment table (SAFE VERSION)
-- This migration checks if columns exist before adding them
-- Run this only on databases where these columns are missing

-- Add feeID column if it doesn't exist
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Payment' AND COLUMN_NAME = 'feeID');

SET @sql = IF(@column_exists = 0,
    'ALTER TABLE Payment ADD COLUMN feeID VARCHAR(10) NULL AFTER classID',
    'SELECT "feeID column already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add term column if it doesn't exist
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Payment' AND COLUMN_NAME = 'term');

SET @sql = IF(@column_exists = 0,
    'ALTER TABLE Payment ADD COLUMN term INT NULL AFTER remark',
    'SELECT "term column already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add academicYear column if it doesn't exist
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Payment' AND COLUMN_NAME = 'academicYear');

SET @sql = IF(@column_exists = 0,
    'ALTER TABLE Payment ADD COLUMN academicYear VARCHAR(10) NULL AFTER term',
    'SELECT "academicYear column already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key constraint if it doesn't exist
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Payment' AND CONSTRAINT_NAME = 'fk_payment_fee');

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE Payment ADD CONSTRAINT fk_payment_fee FOREIGN KEY (feeID) REFERENCES Fees(feeID) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT "fk_payment_fee constraint already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add indexes if they don't exist
SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Payment' AND INDEX_NAME = 'idx_payment_fee');

SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE Payment ADD INDEX idx_payment_fee (feeID)',
    'SELECT "idx_payment_fee index already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Payment' AND INDEX_NAME = 'idx_payment_term');

SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE Payment ADD INDEX idx_payment_term (term)',
    'SELECT "idx_payment_term index already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Payment' AND INDEX_NAME = 'idx_payment_year');

SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE Payment ADD INDEX idx_payment_year (academicYear)',
    'SELECT "idx_payment_year index already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
