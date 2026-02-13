-- =====================================================
-- Migration: Add nationality column to Pupil table
-- Created: February 4, 2026
-- Description: Adds nationality field to store pupil nationality
-- =====================================================

-- Add nationality column to Pupil table (optional field)
ALTER TABLE Pupil 
ADD COLUMN nationality VARCHAR(50) NULL AFTER DoB;

-- Add index for nationality field for faster lookups (only on non-null values)
CREATE INDEX idx_pupil_nationality ON Pupil(nationality);

-- Verification query (optional - run separately to verify)
-- SELECT COUNT(*) as total_records, 
--        COUNT(nationality) as records_with_nationality 
-- FROM Pupil;
