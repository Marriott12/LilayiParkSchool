-- Migration: Create holidays table
-- Date: 2026-01-09
-- Purpose: Track school holidays, term breaks, and events

CREATE TABLE IF NOT EXISTS holidays (
    holidayID INT AUTO_INCREMENT PRIMARY KEY,
    holidayName VARCHAR(100) NOT NULL,
    holidayType ENUM('term_break', 'public_holiday', 'school_event', 'other') NOT NULL DEFAULT 'other',
    startDate DATE NOT NULL,
    endDate DATE NOT NULL,
    academicYear VARCHAR(10) NOT NULL,
    description TEXT,
    createdAt DATETIME NOT NULL,
    INDEX idx_holiday_dates (startDate, endDate),
    INDEX idx_holiday_year (academicYear),
    INDEX idx_holiday_type (holidayType)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default holidays for 2025-2026
INSERT INTO holidays (holidayName, holidayType, startDate, endDate, academicYear, description, createdAt) VALUES
('New Year', 'public_holiday', '2026-01-01', '2026-01-01', '2025-2026', 'New Year''s Day', NOW()),
('Easter Break', 'term_break', '2026-04-03', '2026-04-13', '2025-2026', 'Easter Holiday Break', NOW()),
('Labour Day', 'public_holiday', '2026-05-01', '2026-05-01', '2025-2026', 'Labour Day', NOW()),
('Mid-Year Break', 'term_break', '2026-08-01', '2026-08-23', '2025-2026', 'Term 2 Break', NOW()),
('Independence Day', 'public_holiday', '2026-10-24', '2026-10-24', '2025-2026', 'Zambia Independence Day', NOW()),
('End of Year Break', 'term_break', '2026-12-04', '2027-01-10', '2025-2026', 'Term 3 Break / Christmas Holiday', NOW());
