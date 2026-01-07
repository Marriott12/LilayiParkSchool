-- Lilayi Park School Sample Data
-- Run this after schema.sql

-- Insert Sample Teachers
INSERT INTO Teacher (SSN, Tpin, fName, lName, NRC, phone, email, gender, tczNo) VALUES
('SSN001', 'TPIN001', 'John', 'Banda', 'NRC123456/78/1', '+260977123001', 'john.banda@lilayipark.edu.zm', 'M', 'TCZ001'),
('SSN002', 'TPIN002', 'Mary', 'Mwale', 'NRC234567/89/1', '+260977123002', 'mary.mwale@lilayipark.edu.zm', 'F', 'TCZ002'),
('SSN003', 'TPIN003', 'Peter', 'Phiri', 'NRC345678/90/1', '+260977123003', 'peter.phiri@lilayipark.edu.zm', 'M', 'TCZ003'),
('SSN004', 'TPIN004', 'Grace', 'Tembo', 'NRC456789/01/1', '+260977123004', 'grace.tembo@lilayipark.edu.zm', 'F', 'TCZ004'),
('SSN005', 'TPIN005', 'David', 'Zulu', 'NRC567890/12/1', '+260977123005', 'david.zulu@lilayipark.edu.zm', 'M', 'TCZ005');

-- Insert Sample Parents
INSERT INTO Parent (fName, lName, relation, gender, NRC, phone, email1, email2, occupation, workplace) VALUES
('James', 'Mulenga', 'Father', 'M', 'NRC111111/11/1', '+260977001001', 'james.mulenga@email.com', 'james.m@work.com', 'Engineer', 'Zesco'),
('Alice', 'Mulenga', 'Mother', 'F', 'NRC222222/22/1', '+260977001002', 'alice.mulenga@email.com', NULL, 'Teacher', 'Kabulonga Girls'),
('Robert', 'Sakala', 'Father', 'M', 'NRC333333/33/1', '+260977001003', 'robert.sakala@email.com', 'r.sakala@work.com', 'Doctor', 'UTH'),
('Ruth', 'Sakala', 'Mother', 'F', 'NRC444444/44/1', '+260977001004', 'ruth.sakala@email.com', NULL, 'Nurse', 'Levy Mwanawasa Hospital'),
('Michael', 'Chanda', 'Guardian', 'M', 'NRC555555/55/1', '+260977001005', 'michael.chanda@email.com', NULL, 'Businessman', 'Self Employed'),
('Sarah', 'Banda', 'Mother', 'F', 'NRC666666/66/1', '+260977001006', 'sarah.banda@email.com', 'sarah.b@work.com', 'Accountant', 'Deloitte'),
('Joseph', 'Mwamba', 'Father', 'M', 'NRC777777/77/1', '+260977001007', 'joseph.mwamba@email.com', NULL, 'Lawyer', 'Musa Dudhia & Co');

-- Insert Sample Pupils
INSERT INTO Pupil (fName, sName, gender, DoB, homeAddress, homeArea, medCondition, medAllergy, restrictions, prevSch, reason, parentID, enrollDate, transport, lunch, photo) VALUES
('Chanda', 'Mulenga', 'M', '2015-03-15', 'Plot 123, Makeni Road', 'Makeni', NULL, 'Peanuts', NULL, NULL, NULL, 'PAR001', '2020-01-10', 'Y', 'Y', 'N'),
('Mwansa', 'Mulenga', 'F', '2016-07-22', 'Plot 123, Makeni Road', 'Makeni', NULL, NULL, NULL, NULL, NULL, 'PAR001', '2021-01-15', 'Y', 'Y', 'N'),
('Tamara', 'Sakala', 'F', '2015-11-08', 'House 45, Leopards Hill Road', 'Leopards Hill', 'Asthma', NULL, NULL, 'Roma Basic School', 'Moving to area', 'PAR003', '2020-01-10', 'Y', 'Y', 'N'),
('John', 'Chanda', 'M', '2014-05-20', 'Plot 78, Twin Palm Road', 'Twin Palm', NULL, NULL, 'Vegetarian', NULL, NULL, 'PAR005', '2019-01-08', 'N', 'Y', 'N'),
('Lisa', 'Banda', 'F', '2016-09-12', 'Plot 34, Great East Road', 'Chelston', NULL, 'Lactose', NULL, NULL, NULL, 'PAR006', '2021-01-12', 'Y', 'N', 'N'),
('Nathan', 'Mwamba', 'M', '2015-02-28', 'House 12, Chamba Valley', 'Chamba Valley', NULL, NULL, NULL, NULL, NULL, 'PAR007', '2020-01-10', 'Y', 'Y', 'N'),
('Emma', 'Sakala', 'F', '2017-04-18', 'House 45, Leopards Hill Road', 'Leopards Hill', NULL, NULL, NULL, NULL, NULL, 'PAR003', '2022-01-11', 'Y', 'Y', 'N');

-- Insert Sample Classes
INSERT INTO Class (className, teacherID) VALUES
('Grade 1A', 'TCH001'),
('Grade 1B', 'TCH002'),
('Grade 2A', 'TCH003'),
('Grade 3A', 'TCH004'),
('Grade 4A', 'TCH005');

-- Insert Pupil-Class Assignments
INSERT INTO Pupil_Class (pupilID, classID, enrollmentDate) VALUES
('L001', 'CLS003', '2020-01-10'),
('L002', 'CLS002', '2021-01-15'),
('L003', 'CLS003', '2020-01-10'),
('L004', 'CLS004', '2019-01-08'),
('L005', 'CLS002', '2021-01-12'),
('L006', 'CLS003', '2020-01-10'),
('L007', 'CLS001', '2022-01-11');

-- Insert Sample Fees (for 2025)
INSERT INTO Fees (classID, feeAmt, term, year) VALUES
('CLS001', 2500.00, 'Term 1', 2025),
('CLS001', 2500.00, 'Term 2', 2025),
('CLS001', 2500.00, 'Term 3', 2025),
('CLS002', 2500.00, 'Term 1', 2025),
('CLS002', 2500.00, 'Term 2', 2025),
('CLS002', 2500.00, 'Term 3', 2025),
('CLS003', 3000.00, 'Term 1', 2025),
('CLS003', 3000.00, 'Term 2', 2025),
('CLS003', 3000.00, 'Term 3', 2025),
('CLS004', 3500.00, 'Term 1', 2025),
('CLS004', 3500.00, 'Term 2', 2025),
('CLS004', 3500.00, 'Term 3', 2025),
('CLS005', 4000.00, 'Term 1', 2025),
('CLS005', 4000.00, 'Term 2', 2025),
('CLS005', 4000.00, 'Term 3', 2025);

-- Insert Sample Payments
INSERT INTO Payment (pupilID, classID, pmtAmt, balance, paymentDate, remark) VALUES
('L001', 'CLS003', 3000.00, 0.00, '2025-01-15', 'Full payment Term 1'),
('L002', 'CLS002', 1500.00, 1000.00, '2025-01-20', 'Partial payment Term 1'),
('L003', 'CLS003', 3000.00, 0.00, '2025-01-18', 'Full payment Term 1'),
('L004', 'CLS004', 2000.00, 1500.00, '2025-01-22', 'Partial payment Term 1'),
('L005', 'CLS002', 2500.00, 0.00, '2025-01-25', 'Full payment Term 1'),
('L006', 'CLS003', 1500.00, 1500.00, '2025-01-28', 'Partial payment Term 1');

-- Insert Sample Attendance (for Term 1, 2025)
INSERT INTO Attendance (term, year, pupilID, daysPresent, daysAbsent, remark) VALUES
('Term 1', 2025, 'L001', 45, 2, 'Good attendance'),
('Term 1', 2025, 'L002', 42, 5, 'Some absences'),
('Term 1', 2025, 'L003', 47, 0, 'Perfect attendance'),
('Term 1', 2025, 'L004', 40, 7, 'Frequent absences'),
('Term 1', 2025, 'L005', 44, 3, 'Good attendance'),
('Term 1', 2025, 'L006', 46, 1, 'Excellent attendance'),
('Term 1', 2025, 'L007', 43, 4, 'Good attendance');
