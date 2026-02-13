-- Migration: Add paymentMode column to Payment table
-- Date: 2026-01-12
-- Description: Adds a paymentMode column to track the method of payment (Cash, Bank Transfer, Mobile Money, etc.)

-- Add the paymentMode column
ALTER TABLE Payment 
ADD COLUMN paymentMode VARCHAR(50) DEFAULT 'Cash' AFTER paymentDate;

-- Update existing records to have default value
UPDATE Payment 
SET paymentMode = 'Cash' 
WHERE paymentMode IS NULL;
