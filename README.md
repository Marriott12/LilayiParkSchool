# Lilayi Park School Management System - PHP/Bootstrap Version

## Overview
This is a fully functional school management system built with PHP, MySQL, and Bootstrap 5. It features comprehensive RBAC (Role-Based Access Control) and a scalable modular architecture.

## Features
- ✅ Role-Based Access Control (Admin, Teacher, Parent)
- ✅ Dashboard with real-time statistics
- ✅ Pupil Management
- ✅ Teacher Management
- ✅ Parent Management
- ✅ Class Management
- ✅ Fee Management
- ✅ Payment Tracking
- ✅ Attendance Recording
- ✅ Reports Generation
- ✅ Responsive Bootstrap 5 UI
- ✅ Secure PDO database layer
- ✅ File upload support

## System Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- mod_rewrite enabled (for Apache)

## Installation Instructions

### 1. Database Setup
1. Import the database schema:
   ```sql
   mysql -u root -p lilayiparkschool < ../database/schema.sql
   ```

2. Run the SQL script to create the Users table:
   ```sql
   mysql -u root -p lilayiparkschool < ../database/add_users_table.sql
   ```

### 2. Application Setup
1. Copy the application to your web server directory:
   ```bash
   cp -r php-app /var/www/html/school
   ```

2. Create .env file from example:
   ```bash
   cp .env.example .env
   ```

3. Edit .env with your database credentials:
   ```
   DB_HOST=localhost
   DB_NAME=lilayiparkschool
   DB_USER=your_username
   DB_PASSWORD=your_password
   ```

4. Set proper file permissions:
   ```bash
   chmod -R 755 php-app
   chmod -R 777 php-app/uploads
   ```

### 3. Access the Application
- URL: `http://localhost/school` (or your configured path)
- Default Admin Login:
  - Username: `admin`
  - Password: `admin123`
  - **IMPORTANT:** Change this password immediately after first login!

## Project Structure
```
php-app/
├── config/              # Configuration files
│   ├── config.php      # Main configuration
│   └── database.php    # Database connection
├── includes/            # Core classes and utilities
│   ├── bootstrap.php   # Application bootstrap
│   ├── Session.php     # Session management
│   ├── RBAC.php        # Role-based access control
│   ├── BaseModel.php   # Base model class
│   ├── Utils.php       # Utility functions
│   └── layout.php      # Main layout template
├── modules/             # Application modules
│   ├── auth/           # Authentication
│   ├── pupils/         # Pupil management
│   ├── teachers/       # Teacher management
│   ├── parents/        # Parent management
│   ├── classes/        # Class management
│   ├── fees/           # Fee management
│   ├── payments/       # Payment management
│   ├── attendance/     # Attendance management
│   └── reports/        # Reports
├── assets/              # Static assets
│   ├── css/            # Stylesheets
│   ├── js/             # JavaScript files
│   └── images/         # Images
├── uploads/             # File uploads directory
├── index.php            # Dashboard/Home page
├── login.php            # Login page
└── logout.php           # Logout handler
```

## RBAC Permissions

### Admin Role
- Full access to all modules
- Can create, read, update, and delete all data
- User management capabilities

### Teacher Role
- Read access to pupils and classes
- Full access to attendance management
- Read access to reports

### Parent Role
- Read access to their own children's information
- Read access to their payment records
- Read access to their children's reports

## Security Features
- Password hashing using bcrypt
- PDO prepared statements for SQL injection prevention
- CSRF token protection
- Session timeout (30 minutes)
- XSS prevention through input sanitization
- Role-based access control
- Secure password requirements

## cPanel Deployment

### Option 1: Direct Upload
1. Zip the php-app folder
2. Upload via cPanel File Manager
3. Extract in public_html directory
4. Create database via cPanel MySQL Database
5. Import SQL files via phpMyAdmin
6. Configure .env file

### Option 2: Git Deployment
1. Use cPanel Git Version Control
2. Clone repository
3. Set up database
4. Configure .env

## Module Development Guide

To add a new module:

1. Create module directory: `modules/newmodule/`
2. Create Model: `NewmoduleModel.php` (extends BaseModel)
3. Create Controller: `NewmoduleController.php`
4. Create views: `index.php`, `create.php`, `edit.php`
5. Add menu item in `includes/layout.php`
6. Add permissions in `config/config.php`

## Troubleshooting

### Database Connection Error
- Check .env database credentials
- Verify MySQL service is running
- Ensure database exists

### Permission Denied
- Check file permissions (755 for directories, 644 for files)
- Ensure uploads directory is writable (777)

### Session Issues
- Check session directory permissions
- Verify session.save_path in php.ini

## Support
For issues or questions, please contact the development team.

## License
Proprietary - Lilayi Park School

## Version
1.0.0 - January 2026
