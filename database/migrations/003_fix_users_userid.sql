-- Fix Users table to use VARCHAR(10) for userID to match system pattern
-- This allows linking to Teacher/Parent tables properly

-- Step 1: Drop existing foreign keys that reference Users
ALTER TABLE attendance DROP FOREIGN KEY IF EXISTS attendance_ibfk_4;
ALTER TABLE class DROP FOREIGN KEY IF EXISTS class_ibfk_4;
ALTER TABLE examinations DROP FOREIGN KEY IF EXISTS fk_exam_created_by;
ALTER TABLE examschedule DROP FOREIGN KEY IF EXISTS fk_schedule_invigilator;
ALTER TABLE fees DROP FOREIGN KEY IF EXISTS fees_ibfk_4;
ALTER TABLE payment DROP FOREIGN KEY IF EXISTS payment_ibfk_5;
ALTER TABLE payment DROP FOREIGN KEY IF EXISTS payment_ibfk_6;
ALTER TABLE payment DROP FOREIGN KEY IF EXISTS payment_ibfk_7;
ALTER TABLE payment DROP FOREIGN KEY IF EXISTS payment_ibfk_8;

-- Step 2: Modify Users.userID to VARCHAR(10)
ALTER TABLE Users MODIFY COLUMN userID VARCHAR(10) NOT NULL;

-- Step 3: Drop and recreate primary key
ALTER TABLE Users DROP PRIMARY KEY;
ALTER TABLE Users ADD PRIMARY KEY (userID);

-- Step 4: Recreate foreign keys
ALTER TABLE attendance ADD CONSTRAINT attendance_ibfk_4 FOREIGN KEY (recordedBy) REFERENCES Users(userID) ON DELETE SET NULL;
ALTER TABLE class ADD CONSTRAINT class_ibfk_4 FOREIGN KEY (headTeacher) REFERENCES Users(userID) ON DELETE SET NULL;
ALTER TABLE examinations ADD CONSTRAINT fk_exam_created_by FOREIGN KEY (createdBy) REFERENCES Users(userID) ON DELETE SET NULL;
ALTER TABLE examschedule ADD CONSTRAINT fk_schedule_invigilator FOREIGN KEY (invigilator) REFERENCES Users(userID) ON DELETE SET NULL;
ALTER TABLE fees ADD CONSTRAINT fees_ibfk_4 FOREIGN KEY (createdBy) REFERENCES Users(userID) ON DELETE SET NULL;
ALTER TABLE payment ADD CONSTRAINT payment_ibfk_5 FOREIGN KEY (receivedBy) REFERENCES Users(userID) ON DELETE SET NULL;
ALTER TABLE payment ADD CONSTRAINT payment_ibfk_6 FOREIGN KEY (approvedBy) REFERENCES Users(userID) ON DELETE SET NULL;
ALTER TABLE payment ADD CONSTRAINT payment_ibfk_7 FOREIGN KEY (createdBy) REFERENCES Users(userID) ON DELETE SET NULL;
ALTER TABLE payment ADD CONSTRAINT payment_ibfk_8 FOREIGN KEY (updatedBy) REFERENCES Users(userID) ON DELETE SET NULL;

-- Step 5: Create trigger for Users ID generation
DROP TRIGGER IF EXISTS before_user_insert;
DELIMITER //
CREATE TRIGGER before_user_insert
BEFORE INSERT ON Users
FOR EACH ROW
BEGIN
    IF NEW.userID IS NULL OR NEW.userID = '' THEN
        DECLARE next_id INT;
        DECLARE new_user_id VARCHAR(10);
        
        SELECT IFNULL(MAX(CAST(SUBSTRING(userID, 2) AS UNSIGNED)), 0) + 1 INTO next_id
        FROM Users;
        
        SET new_user_id = CONCAT('U', LPAD(next_id, 3, '0'));
        SET NEW.userID = new_user_id;
    END IF;
END//
DELIMITER ;

SELECT 'Users table updated to VARCHAR(10) userID successfully!' AS Status;
