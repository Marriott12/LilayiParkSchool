-- Migration: Add parent1, parent2, relationship, phone fields to pupils table
ALTER TABLE pupil
  ADD COLUMN parent1 VARCHAR(100) NOT NULL,
  ADD COLUMN parent2 VARCHAR(100) DEFAULT NULL,
  ADD COLUMN relationship VARCHAR(50) NOT NULL,
  ADD COLUMN phone VARCHAR(50) NOT NULL,
  ADD COLUMN parentEmail VARCHAR(100) DEFAULT NULL;
-- If you want to allow NULLs for parent2, relationship, or phone, adjust accordingly.
-- Run this migration in phpMyAdmin or MySQL CLI.