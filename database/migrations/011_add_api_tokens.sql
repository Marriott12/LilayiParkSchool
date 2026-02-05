-- Add API token fields to users table
-- This allows mobile app authentication

ALTER TABLE users 
ADD COLUMN apiToken VARCHAR(64) NULL AFTER password,
ADD COLUMN apiTokenExpires DATETIME NULL AFTER apiToken,
ADD INDEX idx_apiToken (apiToken);
