# Database Deployment Guide for Lilayi Park School

## Overview
This guide explains how to deploy the database schema to a remote MySQL/MariaDB server for production use.

## Prerequisites
- MySQL 8.0+ or MariaDB 10.5+
- Database access credentials (username, password, host)
- SSH access to remote server (if deploying remotely)
- MySQL client or phpMyAdmin access

## Deployment Files

### 1. **full_schema_deployment.sql**
   - Complete database schema with all tables
   - Includes triggers for auto-generating IDs
   - **Run this file FIRST**

### 2. **seed_data_deployment.sql**
   - Default roles, permissions, and role-permission mappings
   - Default admin user (username: `admin`, password: `admin123`)
   - Default subjects and grading scale
   - System settings
   - **Run this file AFTER schema is created**

## Deployment Steps

### Method 1: Using MySQL Command Line

#### Step 1: Connect to MySQL Server
```bash
mysql -h your-database-host -u your-username -p
```

#### Step 2: Create Database
```sql
CREATE DATABASE IF NOT EXISTS lilayiparkschool CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lilayiparkschool;
```

#### Step 3: Import Schema
```bash
mysql -h your-database-host -u your-username -p lilayiparkschool < full_schema_deployment.sql
```

#### Step 4: Import Seed Data
```bash
mysql -h your-database-host -u your-username -p lilayiparkschool < seed_data_deployment.sql
```

#### Step 5: Verify Installation
```bash
mysql -h your-database-host -u your-username -p lilayiparkschool -e "SHOW TABLES;"
```

### Method 2: Using phpMyAdmin

#### Step 1: Login to phpMyAdmin
- Access your server's phpMyAdmin interface
- Login with database credentials

#### Step 2: Create Database
- Click "New" in the left sidebar
- Database name: `lilayiparkschool`
- Collation: `utf8mb4_unicode_ci`
- Click "Create"

#### Step 3: Import Schema
- Select `lilayiparkschool` database
- Click "Import" tab
- Click "Choose File" and select `full_schema_deployment.sql`
- Scroll down and click "Go"
- Wait for import to complete

#### Step 4: Import Seed Data
- Still in `lilayiparkschool` database
- Click "Import" tab again
- Click "Choose File" and select `seed_data_deployment.sql`
- Click "Go"
- Wait for import to complete

#### Step 5: Verify Installation
- Click "Structure" tab
- You should see all tables listed

### Method 3: Using File Upload (for cPanel/Shared Hosting)

#### Step 1: Access File Manager
- Login to cPanel
- Open File Manager
- Navigate to a temporary directory

#### Step 2: Upload SQL Files
- Upload `full_schema_deployment.sql`
- Upload `seed_data_deployment.sql`

#### Step 3: Import via phpMyAdmin
- Follow Method 2 steps above

## Post-Deployment Tasks

### 1. Update Application Configuration
Edit `config/config.php` and `.env` file with production database credentials:

```env
DB_HOST=your-production-host
DB_NAME=lilayiparkschool
DB_USER=your-database-user
DB_PASS=your-database-password
DB_CHARSET=utf8mb4
```

### 2. Change Default Admin Password
**CRITICAL**: The default admin password is `admin123`. Change it immediately!

- Login to the portal with username: `admin`, password: `admin123`
- Navigate to Settings → Change Password
- Or run this SQL query with a new hashed password:

```sql
UPDATE Users 
SET password = '$2y$12$YourNewHashedPassword', 
    mustChangePassword = 'N' 
WHERE username = 'admin';
```

To generate a password hash:
```php
<?php
echo password_hash('your-new-password', PASSWORD_BCRYPT, ['cost' => 12]);
?>
```

### 3. Verify Database Integrity

Run these verification queries:

```sql
-- Check all tables exist
SHOW TABLES;

-- Verify roles
SELECT * FROM Roles;

-- Verify permissions
SELECT COUNT(*) FROM Permissions;

-- Verify admin user
SELECT username, email, role FROM Users WHERE username = 'admin';

-- Verify triggers
SHOW TRIGGERS;

-- Check default subjects
SELECT * FROM Subjects;

-- Check grading scale
SELECT * FROM GradingScale;
```

### 4. Set Proper Permissions (Linux/Unix Servers)

```bash
# Set directory permissions
chmod 755 /path/to/LilayiParkSchool
chmod 755 /path/to/LilayiParkSchool/uploads
chmod 755 /path/to/LilayiParkSchool/logs

# Set file permissions
chmod 644 /path/to/LilayiParkSchool/*.php
chmod 600 /path/to/LilayiParkSchool/.env
chmod 600 /path/to/LilayiParkSchool/config/*.php

# Ensure web server can write to uploads and logs
chown -R www-data:www-data /path/to/LilayiParkSchool/uploads
chown -R www-data:www-data /path/to/LilayiParkSchool/logs
```

## Database Structure Overview

### Core Tables
- **Teacher**: Teacher records with auto-generated IDs (TCH001, TCH002...)
- **Parent**: Parent records with auto-generated IDs (PAR001, PAR002...)
- **Pupil**: Student records with auto-generated IDs (L001, L002...)
- **Class**: Class information (CLS001, CLS002...)
- **Pupil_Class**: Pupil-Class relationships
- **Fees**: Fee structure per class/term/year
- **Payment**: Payment records (PAY001, PAY002...)
- **Attendance**: Term-based attendance summaries

### User Management
- **Users**: System users (admin, teachers, parents)
- **Roles**: User roles (admin, teacher, parent, accountant, librarian)
- **Permissions**: System permissions
- **UserRoles**: User-Role assignments
- **RolePermissions**: Role-Permission assignments

### Academic Management
- **Subjects**: School subjects
- **Grades**: Student grades and marks
- **Examinations**: Examination schedules
- **ExamSchedule**: Exam timetable
- **Timetable**: Class timetables
- **ReportComments**: Report card comments
- **GradingScale**: Grading scale configuration
- **DailyAttendance**: Daily attendance tracking

### Library Management
- **Books**: Library book catalog
- **BorrowRecords**: Book borrowing records

### System
- **Announcements**: School announcements
- **holidays**: School holidays
- **settings**: System configuration settings

## Troubleshooting

### Error: "Table already exists"
- Tables use `CREATE TABLE IF NOT EXISTS` - safe to re-run
- If you need to recreate, manually drop tables first

### Error: "Foreign key constraint fails"
- Ensure tables are created in order (schema file handles this)
- Check if parent tables exist before child tables

### Trigger Errors
- Triggers require `DELIMITER` support
- If using phpMyAdmin, may need to import triggers separately
- Some shared hosting may disable trigger creation

### Character Set Issues
- Ensure database and tables use `utf8mb4`
- Check MySQL version supports utf8mb4 (MySQL 5.5.3+)

### Permission Denied
- Verify database user has CREATE, ALTER, INSERT, and TRIGGER privileges
- Grant permissions: `GRANT ALL PRIVILEGES ON lilayiparkschool.* TO 'username'@'host';`

## Backup and Recovery

### Create Backup
```bash
mysqldump -h host -u username -p lilayiparkschool > backup_$(date +%Y%m%d).sql
```

### Restore from Backup
```bash
mysql -h host -u username -p lilayiparkschool < backup_20260113.sql
```

### Automated Backups (Linux Cron)
```bash
# Add to crontab (runs daily at 2 AM)
0 2 * * * /usr/bin/mysqldump -h host -u user -ppassword lilayiparkschool > /backups/db_$(date +\%Y\%m\%d).sql
```

## Security Checklist

- [ ] Changed default admin password
- [ ] Database user has minimal required privileges
- [ ] .env file has restricted permissions (600)
- [ ] Database connection uses SSL (if available)
- [ ] Regular backups configured
- [ ] Error logging enabled but errors not displayed to users
- [ ] SQL injection protection verified (using PDO prepared statements)
- [ ] CSRF protection enabled
- [ ] Session security configured

## Support

For issues or questions:
- Email: lilayiparkschool@gmail.com
- Phone: +260973116866

## Changelog

**Version 1.0 - January 13, 2026**
- Initial deployment schema
- Complete RBAC system
- Academic management suite
- Library management
- Daily attendance tracking
- Announcements and settings
