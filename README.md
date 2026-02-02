
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

### Local Development Setup (WAMP/XAMPP/LAMP)

#### 1. Database Setup
```bash
# Create database
mysql -u root -p -e "CREATE DATABASE lilayiparkschool CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import schema
mysql -u root -p lilayiparkschool < database/full_schema_deployment.sql

# Import seed data (includes admin user, roles, permissions)
mysql -u root -p lilayiparkschool < database/seed_data_deployment.sql

# Verify deployment
mysql -u root -p lilayiparkschool < database/verify_deployment.sql
```

#### 2. Application Setup
```bash
# Clone or extract files to web directory
# For WAMP: C:\wamp64\www\LilayiParkSchool
# For XAMPP: C:\xampp\htdocs\LilayiParkSchool
# For Linux: /var/www/html/LilayiParkSchool

# Install Composer dependencies
composer install --no-dev --optimize-autoloader

# Create environment file
cp .env.example .env

# Edit .env with your database credentials
# DB_HOST=localhost
# DB_NAME=lilayiparkschool
# DB_USER=root
# DB_PASS=your_password
# APP_ENV=development
```

#### 3. File Permissions
```bash
# Linux/Unix
chmod 755 /var/www/html/LilayiParkSchool
chmod 644 *.php
chmod 600 .env
chmod 755 uploads logs
chown -R www-data:www-data uploads logs

# Windows - Ensure web server user has:
# - Read/Write access to uploads/ and logs/
# - Read access to all other files
```

#### 4. Access the System
- URL: `http://localhost/LilayiParkSchool`
- Default admin login:
  - Username: `admin`
  - Password: `admin123`
- **Important:** Change admin password immediately after first login

---

### Production Deployment

#### Pre-Deployment Checklist
- [ ] Web server (Apache/Nginx) with PHP 7.4+ and MySQL 5.7+
- [ ] SSL certificate configured (HTTPS)
- [ ] Database created with proper user privileges
- [ ] Backup strategy in place
- [ ] Email SMTP credentials ready

#### Deployment Steps

**1. Upload Files**
```bash
# Using Git (recommended)
git clone <repository-url> /var/www/html/LilayiParkSchool
cd /var/www/html/LilayiParkSchool

# Or upload via FTP/SFTP
# Upload all files EXCEPT: .git/, .venv/, scripts/, node_modules/
```

**2. Install Dependencies**
```bash
composer install --no-dev --optimize-autoloader
```

**3. Configure Environment**
```bash
# Create production .env
cp .env.example .env
nano .env

# Set production values:
APP_ENV=production
APP_DEBUG=false
DB_HOST=your-production-host
DB_NAME=lilayiparkschool
DB_USER=your-db-user
DB_PASS=strong-password
BASE_URL=https://yourdomain.com
```

**4. Deploy Database**
```bash
mysql -u your-user -p lilayiparkschool < database/full_schema_deployment.sql
mysql -u your-user -p lilayiparkschool < database/seed_data_deployment.sql
mysql -u your-user -p lilayiparkschool < database/verify_deployment.sql
```

**5. Set Permissions**
```bash
chmod 600 .env
chmod 644 config/*.php
chmod 755 uploads logs
chown -R www-data:www-data uploads logs
```

**6. Configure PHP**
```ini
# In php.ini or .htaccess (production settings)
display_errors = Off
log_errors = On
error_log = /path/to/LilayiParkSchool/logs/php-errors.log
session.cookie_secure = 1
session.cookie_httponly = 1
```

**7. Post-Deployment Tasks**
- [ ] Test login and change admin password
- [ ] Configure SMTP email settings via Settings page
- [ ] Test all critical features (pupils, fees, reports)
- [ ] Set up automated database backups
- [ ] Configure web server (Apache/Nginx) virtual host
- [ ] Test file uploads and permissions
- [ ] Review and clear logs/

#### cPanel Deployment
1. **Upload Files:**
   - Zip project files locally
   - Upload via cPanel File Manager to `public_html/`
   - Extract files

2. **Create Database:**
   - Use MySQL Database Wizard in cPanel
   - Create database and user
   - Grant all privileges
   - Note credentials for `.env`

3. **Import Database:**
   - Open phpMyAdmin from cPanel
   - Select database
   - Import `database/full_schema_deployment.sql`
   - Import `database/seed_data_deployment.sql`
   - Run `database/verify_deployment.sql`

4. **Configure Application:**
   - Rename `.env.example` to `.env`
   - Edit `.env` with database credentials
   - Set `APP_ENV=production`

5. **Set Permissions:**
   - `uploads/` folder: 755
   - `logs/` folder: 755
   - `.env` file: 600

#### Security Hardening (Production)
```bash
# Disable directory listing
echo "Options -Indexes" >> .htaccess

# Protect sensitive files
echo "deny from all" > config/.htaccess
echo "deny from all" > logs/.htaccess

# Force HTTPS
# Add to .htaccess:
# RewriteEngine On
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

#### Backup & Maintenance
```bash
# Database backup (run weekly)
mysqldump -u user -p lilayiparkschool > backup_$(date +%Y%m%d).sql

# Application backup
tar -czf app_backup_$(date +%Y%m%d).tar.gz /var/www/html/LilayiParkSchool

# Clear old logs (monthly)
find logs/ -name "*.log" -mtime +30 -delete
```

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

### Common Issues

**Database Connection Errors**
- Verify `.env` credentials match database
- Check MySQL service is running: `systemctl status mysql` (Linux) or WAMP/XAMPP control panel
- Confirm database exists: `mysql -u root -p -e "SHOW DATABASES;"`
- Test connection: Run `database/verify_deployment.sql`

**Permission Denied / Upload Errors**
- Linux: `chmod 755 uploads logs` and `chown www-data:www-data uploads logs`
- Check PHP upload settings: `upload_max_filesize`, `post_max_size` in php.ini
- Verify disk space: `df -h`

**Session Issues / Logged Out Frequently**
- Check session directory permissions
- Increase session timeout in `php.ini`: `session.gc_maxlifetime = 1800`
- Verify session path is writable: `session.save_path`

**Settings Not Saving**
- Check `logs/php-errors.log` for SQL errors
- Verify Settings table exists: `SHOW TABLES LIKE 'Settings';`
- Clear cache: Access `clear_cache.php` or delete session files

**Email Not Sending**
- Configure SMTP via Settings page in admin panel
- For Gmail: Use App-Specific Password, enable "Less secure app access"
- Test connection: Use "Send Test Email" in Settings
- Check `logs/php-errors.log` for PHPMailer errors

**Form Submission Issues**
- Check browser console for JavaScript errors
- Verify CSRF token is present in form
- Clear browser cache and cookies
- Check `logs/php-errors.log` for validation errors

**404 Errors / Pages Not Found**
- Verify mod_rewrite is enabled (Apache)
- Check `.htaccess` file exists and is readable
- Confirm file permissions allow web server to read PHP files

**Performance Issues**
- Enable PHP opcode cache (OPcache)
- Optimize database: `OPTIMIZE TABLE table_name;`
- Clear old logs: `find logs/ -name "*.log" -mtime +30 -delete`
- Review slow queries in MySQL slow query log

---

## Support
- Email: lilayiparkschool@gmail.com
- Phone: +260973116866

---

## License
Proprietary - Lilayi Park School

## Default Credentials
**Admin Account (Change immediately after first login)**
- Username: `admin`
- Password: `admin123`

## Important Files
- `database/full_schema_deployment.sql` - Complete database schema
- `database/seed_data_deployment.sql` - Default data (roles, admin, settings)
- `database/verify_deployment.sql` - Deployment verification queries
- `.env.example` - Environment configuration template
- `DEPLOYMENT_CHECKLIST.md` - Detailed deployment steps
- `SECURITY.md` - Security guidelines

## Version
1.0.0 - Production Ready - February 2026
