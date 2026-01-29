-- Migration: Change enrollment date format to separate day, month, year columns
ALTER TABLE pupils
  ADD enroll_day INT DEFAULT NULL,
  ADD enroll_month INT DEFAULT NULL,
  ADD enroll_year INT DEFAULT NULL;
-- Optionally, you may want to migrate existing enrollDate values into these new columns.