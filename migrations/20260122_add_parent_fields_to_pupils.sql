
ALTER TABLE Pupil
  ADD COLUMN IF NOT EXISTS parent1 VARCHAR(150) NULL AFTER prevSch,
  ADD COLUMN IF NOT EXISTS parent2 VARCHAR(150) NULL AFTER parent1,
  ADD COLUMN IF NOT EXISTS relationship VARCHAR(50) NULL AFTER parent2,
  ADD COLUMN IF NOT EXISTS phone VARCHAR(50) NULL AFTER relationship,
  ADD COLUMN IF NOT EXISTS parentEmail VARCHAR(150) NULL AFTER phone;

ALTER TABLE Pupil
  MODIFY parentID VARCHAR(10) NULL;

-- Column names added: parent1, parent2, relationship, phone, parentEmail
