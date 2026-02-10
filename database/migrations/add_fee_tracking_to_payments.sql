-- Migration: Add fee tracking columns to Payment table
-- This allows tracking which fee record a payment is associated with

ALTER TABLE Payment 
ADD COLUMN feeID VARCHAR(10) NULL AFTER classID,
ADD COLUMN term INT NULL AFTER remark,
ADD COLUMN academicYear VARCHAR(10) NULL AFTER term;

-- Add foreign key constraint
ALTER TABLE Payment 
ADD CONSTRAINT fk_payment_fee 
FOREIGN KEY (feeID) REFERENCES Fees(feeID) 
ON DELETE SET NULL 
ON UPDATE CASCADE;

-- Add index for better query performance
ALTER TABLE Payment 
ADD INDEX idx_payment_fee (feeID),
ADD INDEX idx_payment_term (term),
ADD INDEX idx_payment_year (academicYear);
