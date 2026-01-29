
# Lilayi Park School Management System - System Documentation

## Overview
Lilayi Park School Management System is a robust, modular PHP/MySQL application for managing all aspects of school operations. It features role-based access control, a dynamic settings system, RESTful mobile API, and secure email notifications.

---

## Features
- Role-Based Access Control (Admin, Teacher, Parent)
- Dashboard with real-time statistics
- Pupil, Teacher, Parent, Class, Fee, Payment, Attendance, and Report Management
- Modular architecture for easy extension
- Responsive Bootstrap 5 UI
- Secure PDO database layer
- File upload support
- Dynamic settings system
- RESTful Mobile API (JSON)
- Email notifications (PHPMailer)

---

## System Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- mod_rewrite enabled (for Apache)

---

## Installation & Deployment

### Database Setup
1. Create database:
   ```bash
   mysql -u root -p -e "CREATE DATABASE lilayiparkschool CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   ```
2. Import schema and seed data:
   ```bash
   mysql -u root -p lilayiparkschool < database/full_schema_deployment.sql
   mysql -u root -p lilayiparkschool < database/seed_data_deployment.sql
   mysql -u root -p lilayiparkschool < database/verify_deployment.sql
   ```

### Application Setup
1. Copy files to web server directory
2. Create `.env` from `.env.example` and configure database credentials
3. Set file permissions (755 for app, 777 for uploads)
4. Access via browser: `http://localhost/LilayiParkSchool`

### cPanel Deployment
- Zip and upload via File Manager, or use Git Version Control
- Create database and import SQL files via phpMyAdmin
- Configure `.env` and permissions

---

## Project Structure
```
LilayiParkSchool/
├── config/        # Configuration files
├── includes/      # Core classes/utilities
├── modules/       # Feature modules (pupils, teachers, parents, etc.)
├── assets/        # Static assets (css, js, images)
├── uploads/       # File uploads
├── database/      # SQL and documentation
├── api/           # API endpoints
├── index.php      # Dashboard
├── login.php      # Login page
└── logout.php     # Logout handler
```

---

## Database Documentation
- 26 tables: Teacher, Parent, Pupil, Class, Fees, Payment, Attendance, Users, Roles, Permissions, Subjects, Grades, Examinations, Timetable, Library, Announcements, Holidays, Settings, etc.
- Auto-generated IDs via triggers (e.g., TCH001, PAR001)
- Default data: roles, permissions, admin user, subjects, grading scale, system settings
- Backup/restore via mysqldump
- Security: change default admin password, use strong DB credentials, enable SSL, regular backups

---

## Settings System
- Dynamic settings table with categories: school, academic, grading, financial, library, notifications, email, reports, system
- SettingsModel API: getSetting, setSetting, getAllSettings, getByCategory
- Session caching for performance
- Form validation and error handling
- Security: CSRF protection, access control, SQL injection prevention, input sanitization
- Best practices for adding new settings

---

## Mobile API Documentation
- RESTful endpoints for mobile integration (JSON)
- Bearer token authentication
- Endpoints: pupils, teachers, parents, attendance, payments, grades, announcements, etc.
- Example usage:
  ```http
  POST /api/mobile/auth.php
  Authorization: Bearer {token}
  ```
- Error responses and rate limiting
- CORS support

---

## Email Configuration
- PHPMailer integration (install via Composer)
- SMTP configuration via settings page
- Supports Gmail, Outlook, and custom SMTP
- Automatic account credential emails
- Password reset and general notifications
- Security: app-specific passwords, SMTP credentials stored securely

---

## Security Features
- Password hashing (bcrypt)
- PDO prepared statements
- CSRF token protection
- Session timeout (default: 30 min)
- XSS prevention
- Role-based access control
- Secure password requirements

---

## Module Development Guide
To add a new module:
1. Create directory: `modules/newmodule/`
2. Create Model: `NewmoduleModel.php` (extends BaseModel)
3. Create Controller: `NewmoduleController.php`
4. Create views: `index.php`, `create.php`, `edit.php`
5. Add menu item in `includes/layout.php`
6. Add permissions in `config/config.php`

---

## Troubleshooting
- Database connection errors: check `.env` credentials, MySQL service, database existence
- Permission denied: check file/directory permissions
- Session issues: check session directory and `php.ini` settings
- Settings not saving: verify table exists, check for unique key violations, review error logs
- Email issues: verify SMTP settings, test with "Send Test Email"

---

## Support
- Email: lilayiparkschool@gmail.com
- Phone: +260973116866

---

## License
Proprietary - Lilayi Park School

## Version
1.0.0 - January 2026
