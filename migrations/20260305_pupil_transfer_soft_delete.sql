-- Migration: Add snapshot columns to Pupil_Transfer and soft-delete columns to Pupil
-- Date: 2026-03-05

-- Add snapshot name columns and futurePaid fields to Pupil_Transfer if missing
ALTER TABLE Pupil_Transfer
  ADD COLUMN IF NOT EXISTS fName VARCHAR(150) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS lName VARCHAR(150) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS futurePaidAmount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  ADD COLUMN IF NOT EXISTS futurePaidTerm VARCHAR(50) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS futurePaidDetails JSON DEFAULT NULL;

-- Add soft-delete / transfer metadata to Pupil table
ALTER TABLE Pupil
  ADD COLUMN IF NOT EXISTS transferred TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS transferredAt DATETIME DEFAULT NULL;

-- Note: Some MySQL versions don't support IF NOT EXISTS in ALTER ADD COLUMN.
-- If your server rejects these statements, run the following variant manually after checking existing columns:
-- ALTER TABLE Pupil_Transfer ADD COLUMN fName VARCHAR(150) DEFAULT NULL;
-- ALTER TABLE Pupil_Transfer ADD COLUMN lName VARCHAR(150) DEFAULT NULL;
-- ALTER TABLE Pupil_Transfer ADD COLUMN futurePaidAmount DECIMAL(10,2) NOT NULL DEFAULT 0.00;
-- ALTER TABLE Pupil_Transfer ADD COLUMN futurePaidTerm VARCHAR(50) DEFAULT NULL;
-- ALTER TABLE Pupil_Transfer ADD COLUMN futurePaidDetails JSON DEFAULT NULL;
-- ALTER TABLE Pupil ADD COLUMN transferred TINYINT(1) NOT NULL DEFAULT 0;
-- ALTER TABLE Pupil ADD COLUMN transferredAt DATETIME DEFAULT NULL;
