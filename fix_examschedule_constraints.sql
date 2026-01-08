-- SQL Fixes for ExamSchedule Foreign Keys
-- Run these in phpMyAdmin one at a time

-- Step 1: Check what data type Class.classID uses
-- Run this first to see the result:
-- DESCRIBE Class;

-- Step 2A: If Class.classID is VARCHAR, run this:
-- ALTER TABLE ExamSchedule MODIFY classID VARCHAR(50);

-- Step 2B: If Class.classID is INT, it's already correct

-- Step 3: Add the classID constraint (adjust table name if needed)
ALTER TABLE ExamSchedule
ADD CONSTRAINT fk_schedule_class 
FOREIGN KEY (classID) REFERENCES Class(classID) ON DELETE CASCADE;

-- Step 4: Add the subjectID constraint (capital S in Subjects)
ALTER TABLE ExamSchedule
ADD CONSTRAINT fk_schedule_subject 
FOREIGN KEY (subjectID) REFERENCES Subjects(subjectID) ON DELETE CASCADE;

-- Step 5: Add the invigilator constraint
ALTER TABLE ExamSchedule
ADD CONSTRAINT fk_schedule_invigilator 
FOREIGN KEY (invigilator) REFERENCES Users(userID) ON DELETE SET NULL;

-- If any constraint fails with "incompatible" error, you need to match data types:
-- Common fixes:
-- 
-- For VARCHAR mismatch:
-- ALTER TABLE ExamSchedule MODIFY classID VARCHAR(50);
-- ALTER TABLE ExamSchedule MODIFY subjectID VARCHAR(50);
-- 
-- For INT vs BIGINT mismatch:
-- ALTER TABLE ExamSchedule MODIFY classID BIGINT;
-- ALTER TABLE ExamSchedule MODIFY subjectID BIGINT;
