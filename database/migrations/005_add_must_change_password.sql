-- Add mustChangePassword column to Users table
-- This allows forcing users to change their password on first login

ALTER TABLE Users 
ADD COLUMN mustChangePassword ENUM('Y', 'N') NOT NULL DEFAULT 'N' 
AFTER isActive;

-- Set existing users to not require password change
UPDATE Users SET mustChangePassword = 'N' WHERE mustChangePassword IS NULL;
