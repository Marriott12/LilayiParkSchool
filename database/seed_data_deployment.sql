-- =====================================================
-- Lilayi Park School - Seed Data for Deployment
-- Generated: January 13, 2026
-- Run AFTER full_schema_deployment.sql
-- =====================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- =====================================================
-- DEFAULT ROLES
-- =====================================================

INSERT INTO Roles (roleID, roleName, description) VALUES
('R001', 'admin', 'System Administrator - Full access to all modules and settings'),
('R002', 'teacher', 'Teacher - Access to teaching modules, grades, and class management'),
('R003', 'parent', 'Parent - Access to child information, grades, and communications'),
('R004', 'accountant', 'Accountant - Manage school finances and fee payments'),
('R005', 'librarian', 'Librarian - Manage library books and resources')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- =====================================================
-- DEFAULT PERMISSIONS
-- =====================================================

INSERT INTO Permissions (permissionID, permissionName, module, action, description) VALUES
-- User Management
('P001', 'manage_users', 'users', 'all', 'Full user management'),
('P024', 'view_users', 'users', 'read', 'View users'),

-- Role Management
('P002', 'manage_roles', 'roles', 'all', 'Manage roles and permissions'),

-- Teachers
('P003', 'view_teachers', 'teachers', 'read', 'View teacher information'),
('P004', 'manage_teachers', 'teachers', 'all', 'Full teacher management'),

-- Parents
('P005', 'view_parents', 'parents', 'read', 'View parent information'),
('P006', 'manage_parents', 'parents', 'all', 'Full parent management'),

-- Pupils
('P007', 'view_pupils', 'pupils', 'read', 'View pupil information'),
('P008', 'manage_pupils', 'pupils', 'all', 'Full pupil management'),

-- Grades
('P009', 'view_grades', 'grades', 'read', 'View grades and academic records'),
('P010', 'manage_grades', 'grades', 'all', 'Enter and modify grades'),

-- Classes
('P011', 'view_classes', 'classes', 'read', 'View class information'),
('P012', 'manage_classes', 'classes', 'all', 'Full class management'),

-- Attendance
('P013', 'view_attendance', 'attendance', 'read', 'View attendance records'),
('P014', 'manage_attendance', 'attendance', 'all', 'Mark and manage attendance'),
('P017', 'view_attendance', 'reports', 'read', 'View attendance reports'),

-- Fees and Payments
('P015', 'view_payments', 'payments', 'read', 'View payment records'),
('P016', 'manage_payments', 'payments', 'all', 'Manage fees and payments'),

-- Subjects
('P018', 'view_subjects', 'subjects', 'read', 'View subjects'),
('P019', 'manage_subjects', 'subjects', 'all', 'Manage subjects'),

-- Library
('P021', 'view_library', 'library', 'view', 'View library and books'),
('P022', 'manage_library', 'library', 'manage', 'Manage books and borrow records'),

-- Announcements
('P023', 'manage_announcements', 'announcements', 'all', 'Manage announcements')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- =====================================================
-- ROLE PERMISSIONS MAPPING
-- =====================================================

-- Admin: All permissions
INSERT INTO RolePermissions (roleID, permissionID) VALUES
('R001', 'P001'), ('R001', 'P002'), ('R001', 'P003'), ('R001', 'P004'),
('R001', 'P005'), ('R001', 'P006'), ('R001', 'P007'), ('R001', 'P008'),
('R001', 'P009'), ('R001', 'P010'), ('R001', 'P011'), ('R001', 'P012'),
('R001', 'P013'), ('R001', 'P014'), ('R001', 'P015'), ('R001', 'P016'),
('R001', 'P017'), ('R001', 'P018'), ('R001', 'P019'), ('R001', 'P021'),
('R001', 'P022'), ('R001', 'P023'), ('R001', 'P024')
ON DUPLICATE KEY UPDATE permissionID = VALUES(permissionID);

-- Teacher: View and manage academic functions
INSERT INTO RolePermissions (roleID, permissionID) VALUES
('R002', 'P003'), -- view_teachers
('R002', 'P007'), -- view_pupils
('R002', 'P009'), -- view_grades
('R002', 'P010'), -- manage_grades
('R002', 'P011'), -- view_classes
('R002', 'P013'), -- view_attendance
('R002', 'P014'), -- manage_attendance
('R002', 'P017'), -- view_attendance_reports
('R002', 'P018'), -- view_subjects
('R002', 'P021')  -- view_library
ON DUPLICATE KEY UPDATE permissionID = VALUES(permissionID);

-- Parent: View only their child's information
INSERT INTO RolePermissions (roleID, permissionID) VALUES
('R003', 'P007'), -- view_pupils
('R003', 'P009'), -- view_grades
('R003', 'P013'), -- view_attendance
('R003', 'P015'), -- view_payments
('R003', 'P021')  -- view_library
ON DUPLICATE KEY UPDATE permissionID = VALUES(permissionID);

-- Accountant: Manage finances
INSERT INTO RolePermissions (roleID, permissionID) VALUES
('R004', 'P007'), -- view_pupils
('R004', 'P011'), -- view_classes
('R004', 'P015'), -- view_payments
('R004', 'P016')  -- manage_payments
ON DUPLICATE KEY UPDATE permissionID = VALUES(permissionID);

-- Librarian: Manage library
INSERT INTO RolePermissions (roleID, permissionID) VALUES
('R005', 'P007'), -- view_pupils
('R005', 'P021'), -- view_library
('R005', 'P022')  -- manage_library
ON DUPLICATE KEY UPDATE permissionID = VALUES(permissionID);

-- =====================================================
-- DEFAULT ADMIN USER
-- Password: admin123 (MUST BE CHANGED IN PRODUCTION!)
-- =====================================================

INSERT INTO Users (username, email, password, role, firstName, lastName, isActive) VALUES
('admin', 'admin@lilayipark.edu.zm', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyVVpVKXp.fC', 'admin', 'System', 'Administrator', 'Y')
ON DUPLICATE KEY UPDATE username = username;

-- Assign admin role
INSERT INTO UserRoles (userID, roleID)
SELECT u.userID, 'R001'
FROM Users u
WHERE u.username = 'admin'
ON DUPLICATE KEY UPDATE roleID = VALUES(roleID);

-- =====================================================
-- DEFAULT SUBJECTS
-- =====================================================

INSERT INTO Subjects (subjectCode, subjectName, description, isActive) VALUES
('ENG', 'English Language', 'English Language and Literature', TRUE),
('MATH', 'Mathematics', 'Mathematics', TRUE),
('SCI', 'Science', 'Integrated Science', TRUE),
('SST', 'Social Studies', 'Social Studies', TRUE),
('CRE', 'Religious Education', 'Christian Religious Education', TRUE),
('PE', 'Physical Education', 'Physical Education and Sports', TRUE),
('ART', 'Creative Arts', 'Art and Music', TRUE),
('ICT', 'Information Technology', 'Computer Studies and ICT', TRUE)
ON DUPLICATE KEY UPDATE subjectName = VALUES(subjectName);

-- =====================================================
-- GRADING SCALE
-- =====================================================

INSERT INTO GradingScale (minMarks, maxMarks, grade, gradePoint, description, isActive) VALUES
(80, 100, 'A', 4.00, 'Excellent', TRUE),
(70, 79, 'B', 3.00, 'Very Good', TRUE),
(60, 69, 'C', 2.00, 'Good', TRUE),
(50, 59, 'D', 1.00, 'Fair', TRUE),
(40, 49, 'E', 0.50, 'Pass', TRUE),
(0, 39, 'F', 0.00, 'Fail', TRUE)
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- =====================================================
-- SYSTEM SETTINGS
-- =====================================================

INSERT INTO settings (settingKey, settingValue, category) VALUES
-- School Information
('school_name', 'Lilayi Park School', 'school'),
('school_address', 'Lilayi, Lusaka, Zambia', 'school'),
('school_phone', '+260973116866', 'school'),
('school_email', 'lilayiparkschool@gmail.com', 'school'),
('school_motto', 'Excellence Through Education', 'school'),

-- Academic Settings
('current_term', '1', 'academic'),
('current_academic_year', '2025-2026', 'academic'),
('term_1_start', '2025-09-01', 'academic'),
('term_1_end', '2025-12-15', 'academic'),
('term_2_start', '2026-01-05', 'academic'),
('term_2_end', '2026-04-10', 'academic'),
('term_3_start', '2026-04-20', 'academic'),
('term_3_end', '2026-07-25', 'academic'),
('attendance_threshold', '75', 'academic'),
('passing_mark', '40', 'academic'),
('max_absences', '15', 'academic'),

-- Grading Scale
('grade_a_min', '80', 'grading'),
('grade_b_min', '70', 'grading'),
('grade_c_min', '60', 'grading'),
('grade_d_min', '50', 'grading'),
('passing_grade', '50', 'grading'),

-- Financial Settings
('currency', 'ZMW', 'financial'),
('late_fee_penalty', '5', 'financial'),

-- Library Settings
('library_borrow_days', '14', 'library'),
('library_fine_per_day', '0.50', 'library'),
('library_max_books', '3', 'library')
ON DUPLICATE KEY UPDATE settingValue = VALUES(settingValue);

-- =====================================================
-- END OF SEED DATA
-- =====================================================

SELECT 'Seed data installation complete!' AS Status;
