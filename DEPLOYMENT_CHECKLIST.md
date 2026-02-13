# Remote Server Deployment Checklist

Use this checklist when deploying to the remote/production server.

## Pre-Deployment

- [ ] Have remote server credentials (host, username, password)
- [ ] Have MySQL/MariaDB access on remote server
- [ ] Downloaded latest deployment files from GitHub
- [ ] Reviewed DEPLOYMENT_GUIDE.md
- [ ] Backup existing database (if any)

## Database Deployment

- [ ] Connect to remote MySQL server
- [ ] Create database `lilayiparkschool` with utf8mb4 charset
- [ ] Import `full_schema_deployment.sql`
- [ ] Import `seed_data_deployment.sql`
- [ ] Run `verify_deployment.sql` to check
- [ ] Confirm 26 tables created
- [ ] Confirm 5 triggers created
- [ ] Verify admin user exists

## Application Configuration

- [ ] Upload all application files to remote server
- [ ] Create `.env` file with production settings
- [ ] Update database credentials in `.env`:
  ```
  DB_HOST=your-production-host
  DB_NAME=lilayiparkschool
  DB_USER=your-db-user
  DB_PASS=your-db-password
  ```
- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set correct `BASE_URL` in `config/config.php`
- [ ] Ensure error logging enabled but errors not displayed
- [ ] Configure SMTP settings for email notifications

## Security

- [ ] Change admin password from default `admin123`
- [ ] Set `.env` file permissions to 600 (Linux/Unix)
- [ ] Set config files permissions appropriately
- [ ] Create uploads directory with write permissions
- [ ] Create logs directory with write permissions
- [ ] Verify database user has minimal required privileges
- [ ] Enable HTTPS/SSL on web server
- [ ] Configure secure session settings
- [ ] Test CSRF protection is working

## File Permissions (Linux/Unix Servers)

```bash
# Application root
chmod 755 /path/to/LilayiParkSchool

# Config files (read-only)
chmod 600 /path/to/LilayiParkSchool/.env
chmod 644 /path/to/LilayiParkSchool/config/*.php

# Writable directories
chmod 755 /path/to/LilayiParkSchool/uploads
chmod 755 /path/to/LilayiParkSchool/logs
chown -R www-data:www-data /path/to/LilayiParkSchool/uploads
chown -R www-data:www-data /path/to/LilayiParkSchool/logs

# PHP files
chmod 644 /path/to/LilayiParkSchool/*.php
```

## Initial Data Setup

- [ ] Login with admin credentials
- [ ] Change admin password via portal
- [ ] Add teacher records
- [ ] Create teacher user accounts
- [ ] Add parent records
- [ ] Create parent user accounts
- [ ] Add student (pupil) records
- [ ] Create classes
- [ ] Assign class teachers
- [ ] Enroll pupils in classes
- [ ] Set fee structure for each class/term
- [ ] Configure system settings (academic year, terms, etc.)

## Optional Modules Setup

### Library Module
- [ ] Add library books
- [ ] Configure library settings (borrow days, fines)
- [ ] Assign librarian role to user

### Academic Module
- [ ] Create examination schedules
- [ ] Set up timetables
- [ ] Configure grading scale (if different from default)

### Announcements
- [ ] Create welcome announcement
- [ ] Test announcement visibility by role

## Testing

- [ ] Test admin login
- [ ] Test teacher login
- [ ] Test parent login
- [ ] Test pupil enrollment
- [ ] Test fee payment recording
- [ ] Test grade entry
- [ ] Test attendance marking
- [ ] Test report card generation
- [ ] Test library borrowing (if enabled)
- [ ] Test user permissions (teacher can't access admin functions)
- [ ] Test parent can only see their child's info
- [ ] Test mobile API endpoints (if using mobile app)
- [ ] Test email notifications
- [ ] Test file uploads (photos, documents)
- [ ] Test exports (PDF, Excel)

## Performance & Monitoring

- [ ] Enable query caching in MySQL
- [ ] Configure PHP OPcache
- [ ] Set up automated database backups
- [ ] Configure error logging
- [ ] Set up monitoring/alerts (optional)
- [ ] Test website load time
- [ ] Verify database indexes are being used

## Backup Configuration

### Automated MySQL Backups (Linux Cron)

```bash
# Daily backup at 2 AM
0 2 * * * /usr/bin/mysqldump -h host -u user -p'password' lilayiparkschool > /backups/db_$(date +\%Y\%m\%d).sql

# Weekly backup on Sunday at 3 AM
0 3 * * 0 /usr/bin/mysqldump -h host -u user -p'password' lilayiparkschool | gzip > /backups/weekly_$(date +\%Y\%m\%d).sql.gz

# Delete backups older than 30 days
0 4 * * * find /backups -name "db_*.sql" -mtime +30 -delete
```

- [ ] Set up daily database backups
- [ ] Set up weekly full backups
- [ ] Set up file backups (uploads, logs)
- [ ] Test backup restoration process
- [ ] Document backup locations and procedures

## Documentation

- [ ] Document server credentials (securely)
- [ ] Document database connection details
- [ ] Document backup procedures
- [ ] Document deployment process for future updates
- [ ] Create admin user guide
- [ ] Create teacher user guide
- [ ] Create parent user guide

## Post-Deployment

- [ ] Send admin credentials to school administrator
- [ ] Provide training to staff
- [ ] Monitor system for first week
- [ ] Collect user feedback
- [ ] Address any issues promptly

## Verification Queries

Run these in MySQL to verify deployment:

```sql
-- Check all tables exist (should be 26)
SELECT COUNT(*) FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'lilayiparkschool';

-- Verify admin user
SELECT username, email, role, isActive 
FROM Users WHERE username = 'admin';

-- Check roles and permissions
SELECT r.roleName, COUNT(rp.permissionID) as permission_count
FROM Roles r
LEFT JOIN RolePermissions rp ON r.roleID = rp.roleID
GROUP BY r.roleID;

-- Verify triggers
SELECT TRIGGER_NAME FROM information_schema.TRIGGERS 
WHERE TRIGGER_SCHEMA = 'lilayiparkschool';
```

## Emergency Contacts

- **School**: +260973116866
- **Email**: lilayiparkschool@gmail.com
- **Developer Support**: [Add contact info]

## Rollback Plan

If deployment fails:

1. Restore database from backup
2. Revert application files
3. Check error logs for issues
4. Document what went wrong
5. Fix issues in development
6. Retry deployment

---

## Quick Reference

**Default Admin Credentials**
- Username: `admin`
- Password: `admin123`
- ⚠️ **CHANGE IMMEDIATELY AFTER FIRST LOGIN**

**Database Details**
- Database Name: `lilayiparkschool`
- Character Set: `utf8mb4`
- Collation: `utf8mb4_unicode_ci`
- Total Tables: 26
- Engine: InnoDB

**Deployment Files**
1. `full_schema_deployment.sql` - Database schema
2. `seed_data_deployment.sql` - Default data
3. `verify_deployment.sql` - Verification script

---

**Date Deployed**: _______________  
**Deployed By**: _______________  
**Server**: _______________  
**Database Version**: _______________  
**PHP Version**: _______________
