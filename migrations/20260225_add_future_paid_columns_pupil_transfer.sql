-- Migration: add future payment metadata to Pupil_Transfer
-- Date: 2026-02-25

ALTER TABLE Pupil_Transfer
  ADD COLUMN IF NOT EXISTS futurePaidAmount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  ADD COLUMN IF NOT EXISTS futurePaidTerm VARCHAR(50) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS futurePaidDetails JSON DEFAULT NULL;
