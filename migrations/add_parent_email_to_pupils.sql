-- Migration: Add parent_email field to pupils table
ALTER TABLE pupils
  ADD parent_email VARCHAR(100) DEFAULT NULL;
