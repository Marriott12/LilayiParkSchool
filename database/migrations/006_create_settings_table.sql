-- Migration: Create Settings Table
-- This table stores system configuration settings
-- Created: 2025

-- Create settings table if it doesn't exist
CREATE TABLE IF NOT EXISTS settings (
    settingID INT AUTO_INCREMENT PRIMARY KEY,
    settingKey VARCHAR(100) UNIQUE NOT NULL,
    settingValue TEXT NULL,
    category VARCHAR(50) DEFAULT 'general',
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_settingKey (settingKey)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings if they don't exist
INSERT IGNORE INTO settings (settingKey, settingValue, category) VALUES
-- School Information
('school_name', 'Lilayi Park School', 'school'),
('school_address', '', 'school'),
('school_phone', '+260973116866', 'school'),
('school_email', 'lilayiparkschool@gmail.com', 'school'),
('school_motto', '', 'school'),

-- Academic Settings
('current_term', '1', 'academic'),
('current_academic_year', '2025/2026', 'academic'),
('term1_start', '', 'academic'),
('term1_end', '', 'academic'),
('term2_start', '', 'academic'),
('term2_end', '', 'academic'),
('term3_start', '', 'academic'),
('term3_end', '', 'academic'),
('attendance_threshold', '75', 'academic'),

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
('library_fine_per_day', '0.50', 'library'),
('library_max_books', '3', 'library'),
('library_loan_period', '14', 'library'),

-- Notification Settings
('notifications_enabled', '0', 'notifications'),
('email_notifications', '0', 'notifications'),
('sms_notifications', '0', 'notifications'),
('sms_api_key', '', 'notifications'),

-- Email/SMTP Configuration
('smtp_host', '', 'email'),
('smtp_port', '587', 'email'),
('smtp_username', '', 'email'),
('smtp_password', '', 'email'),
('smtp_encryption', 'tls', 'email'),
('smtp_from_email', '', 'email'),
('smtp_from_name', 'Lilayi Park School', 'email'),
('send_account_emails', '0', 'email'),

-- Report Card Settings
('report_show_position', '1', 'reports'),
('report_show_average', '1', 'reports'),
('report_show_attendance', '1', 'reports'),
('report_head_signature', '', 'reports'),

-- System Maintenance
('maintenance_mode', '0', 'system'),
('session_timeout', '30', 'system');
