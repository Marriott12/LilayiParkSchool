# Database Documentation

This folder contains all database-related files for the Lilayi Park School Management System.

## 📁 Files for Deployment

### Primary Deployment Files (Use These!)

1. **full_schema_deployment.sql** ⭐
   - Complete database schema with all tables
   - Includes auto-increment triggers
   - **Run this FIRST** on new installations

2. **seed_data_deployment.sql** ⭐
   - Default roles, permissions, and mappings
   - Default admin user (username: `admin`, password: `admin123`)
   - Default subjects and grading scale
   - System settings
   - **Run this AFTER schema**

3. **verify_deployment.sql** ✅
   - Verification script to check deployment
   - **Run this AFTER seeding** to verify everything installed correctly

### Documentation Files

4. **DEPLOYMENT_GUIDE.md** 📖
   - Complete deployment instructions
   - Multiple deployment methods (CLI, phpMyAdmin, cPanel)
   - Post-deployment tasks
   - Troubleshooting guide
   - **READ THIS BEFORE DEPLOYING**

5. **TABLE_REFERENCE.md** 📊
   - Complete list of all 26 tables
   - Foreign key relationships
   - Table purposes and expected sizes
   - Index strategy

## 🚀 Quick Start Deployment

### Option 1: MySQL Command Line (Recommended)

```bash
# 1. Create database
mysql -u root -p -e "CREATE DATABASE lilayiparkschool CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Import schema
mysql -u root -p lilayiparkschool < full_schema_deployment.sql

# 3. Import seed data
mysql -u root -p lilayiparkschool < seed_data_deployment.sql

# 4. Verify deployment
mysql -u root -p lilayiparkschool < verify_deployment.sql
```

### Option 2: phpMyAdmin

1. Login to phpMyAdmin
2. Create database `lilayiparkschool` with collation `utf8mb4_unicode_ci`
3. Select the database
4. Go to Import tab
5. Import `full_schema_deployment.sql`
6. Import `seed_data_deployment.sql`
7. Run `verify_deployment.sql` in SQL tab

## 📋 Database Overview

### Total Tables: 26

**Core Tables (8)**
- Teacher, Parent, Pupil, Class, Pupil_Class, Fees, Payment, Attendance

**User Management (5)**
- Users, Roles, Permissions, UserRoles, RolePermissions

**Academic Management (8)**
- Subjects, Grades, Examinations, ExamSchedule, Timetable, ReportComments, GradingScale, DailyAttendance

**Library (2)**
- Books, BorrowRecords

**System (3)**
- Announcements, holidays, settings

### Default Data Included

✅ **5 Roles**: admin, teacher, parent, accountant, librarian  
✅ **24 Permissions**: Full RBAC permission set  
✅ **1 Admin User**: username `admin`, password `admin123` (⚠️ CHANGE THIS!)  
✅ **8 Subjects**: English, Math, Science, etc.  
✅ **Grading Scale**: A-F with grade points  
✅ **System Settings**: Academic year, terms, library settings

## 🔧 Legacy Files (Reference Only)

The following files are kept for reference but are **NOT needed for deployment**:

- `schema.sql` - Original base schema (superseded by full_schema_deployment.sql)
- `seed.sql` - Original seed data (superseded by seed_data_deployment.sql)
- `add_users_table.sql` - Users table migration (included in full schema)
- `migrations/` folder - All migrations are consolidated into full_schema_deployment.sql

**For new installations, only use the deployment files listed above.**

## ⚙️ Auto-Generated IDs

The database uses triggers to auto-generate custom IDs:

| Table | ID Format | Example |
|-------|-----------|---------|
| Teacher | TCH### | TCH001, TCH002 |
| Parent | PAR### | PAR001, PAR002 |
| Pupil | L### | L001, L002 |
| Class | CLS### | CLS001, CLS002 |
| Payment | PAY### | PAY001, PAY002 |

When inserting records, **leave the ID field empty or NULL** - triggers will generate it automatically.

## 🔐 Security Notes

1. **Change default admin password immediately after deployment**
2. Use strong database passwords
3. Grant minimal required privileges to database users
4. Keep `.env` file secure (permissions 600)
5. Enable SSL for database connections in production
6. Regular backups are critical

## 📞 Support

For deployment issues or questions:
- **Email**: lilayiparkschool@gmail.com
- **Phone**: +260973116866

## 📝 Version Information

- **Database Version**: 1.0
- **Last Updated**: January 13, 2026
- **MySQL Version**: 8.0+ (recommended)
- **MariaDB Version**: 10.5+ (supported)
- **Character Set**: utf8mb4
- **Collation**: utf8mb4_unicode_ci
- **Engine**: InnoDB

## 🔄 Backup & Restore

### Create Backup
```bash
mysqldump -u username -p lilayiparkschool > backup_$(date +%Y%m%d).sql
```

### Restore Backup
```bash
mysql -u username -p lilayiparkschool < backup_20260113.sql
```

### Backup Schedule (Recommended)
- **Daily**: Pupil, Payment, Grades, Users
- **Weekly**: Teacher, Parent, Class, Attendance
- **Monthly**: All other tables

## 🎯 Next Steps After Deployment

1. ✅ Verify deployment with `verify_deployment.sql`
2. ⚠️ Change admin password
3. 📝 Add teachers via admin panel
4. 📝 Add parents via admin panel  
5. 📝 Add students via admin panel
6. 📝 Create classes
7. 📝 Set fee structure
8. 🔧 Configure system settings
9. 📚 Add library books (if using library module)
10. 🔔 Create announcements

## 📖 Related Documentation

- [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) - Detailed deployment instructions
- [TABLE_REFERENCE.md](TABLE_REFERENCE.md) - Complete table documentation
- [../SECURITY.md](../SECURITY.md) - Security best practices
- [../README.md](../README.md) - Main project documentation

---

**Ready to deploy?** Start with [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)
