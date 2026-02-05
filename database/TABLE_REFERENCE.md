# Database Table Reference - Lilayi Park School

## Complete Table List (33 Tables)

### Core School Management (8 Tables)
1. **Teacher** - Teacher information and credentials
2. **Parent** - Parent/guardian information
3. **Pupil** - Student records
4. **Class** - Class/grade information
5. **Pupil_Class** - Student-class assignments (junction table)
6. **Fees** - Fee structure per class/term
7. **Payment** - Student payment records
8. **Attendance** - Term-based attendance summaries

### User Authentication & Authorization (5 Tables)
9. **Users** - System user accounts
10. **Roles** - User roles (Admin, Teacher, Parent, etc.)
11. **Permissions** - System permissions
12. **UserRoles** - User-role assignments (junction table)
13. **RolePermissions** - Role-permission assignments (junction table)

### Academic Management (8 Tables)
14. **Subjects** - School subjects/courses
15. **Grades** - Student grades and exam results
16. **Examinations** - Examination definitions
17. **ExamSchedule** - Exam dates and times
18. **Timetable** - Class schedules
19. **ReportComments** - Report card comments
20. **GradingScale** - Grading scale configuration
21. **DailyAttendance** - Daily attendance tracking

### Library Management (2 Tables)
22. **Books** - Library book catalog
23. **BorrowRecords** - Book borrowing transactions

### Communication & Settings (3 Tables)
24. **Announcements** - School announcements
25. **holidays** - School holiday calendar
26. **settings** - System configuration

## Tables Requiring Manual Data Entry

After deployment, these tables need data:
- ✅ **Teacher** - Add teachers
- ✅ **Parent** - Add parents
- ✅ **Pupil** - Add students
- ✅ **Class** - Create classes
- ✅ **Fees** - Set fee structure

## Tables with Default Data

These tables come pre-populated:
- ✅ **Roles** - 5 default roles
- ✅ **Permissions** - 24 permissions
- ✅ **RolePermissions** - Permission assignments
- ✅ **Users** - 1 admin user
- ✅ **UserRoles** - Admin role assignment
- ✅ **Subjects** - 8 default subjects
- ✅ **GradingScale** - 6 grade levels
- ✅ **settings** - System defaults

## Auto-Generated ID Tables

These tables use triggers for ID generation:
- **Teacher** → TCH001, TCH002, TCH003...
- **Parent** → PAR001, PAR002, PAR003...
- **Pupil** → L001, L002, L003...
- **Class** → CLS001, CLS002, CLS003...
- **Payment** → PAY001, PAY002, PAY003...

## Foreign Key Relationships

```
Parent (parentID) ←─── Pupil (parentID)
                       └──→ Pupil_Class ←─── Class (classID) ←─── Teacher (teacherID)
                                                     │
                                                     └──→ Fees
                       
Pupil (pupilID) ─→ Payment
                ├─→ Attendance
                ├─→ DailyAttendance
                ├─→ Grades
                ├─→ BorrowRecords
                └─→ ReportComments

Users (userID) ─→ UserRoles ←─── Roles (roleID) ─→ RolePermissions ←─── Permissions
              │
              ├─→ Teacher (userID)
              ├─→ Parent (userID)
              ├─→ Grades (recordedBy)
              ├─→ Examinations (createdBy)
              ├─→ BorrowRecords (issuedBy, returnedTo)
              ├─→ DailyAttendance (markedBy)
              └─→ Announcements (createdBy)

Subjects (subjectID) ─→ Grades
                     ├─→ ExamSchedule
                     └─→ Timetable
```

## Table Sizes (Estimated)

| Table | Expected Size | Growth Rate |
|-------|--------------|-------------|
| Pupil | 200-500 | Medium |
| Teacher | 20-50 | Low |
| Parent | 200-500 | Medium |
| Class | 10-20 | Low |
| Grades | 10,000+ | High |
| DailyAttendance | 50,000+ | High |
| Payment | 5,000+ | Medium |
| BorrowRecords | 2,000+ | Medium |
| Announcements | 100-500 | Low |
| Users | 50-100 | Low |

## Index Strategy

All tables include appropriate indexes for:
- ✅ Primary keys
- ✅ Foreign keys
- ✅ Frequently searched columns
- ✅ Unique constraints
- ✅ Date ranges
- ✅ Status fields

## Character Set & Collation

All tables use:
- **Character Set**: utf8mb4
- **Collation**: utf8mb4_unicode_ci
- **Engine**: InnoDB (supports transactions and foreign keys)

## Backup Priority

### Critical (Daily backups)
1. Pupil
2. Payment
3. Grades
4. Users

### Important (Weekly backups)
5. Teacher
6. Parent
7. Class
8. Attendance
9. DailyAttendance

### Standard (Monthly backups)
10. All other tables

## Migration History

All migrations are consolidated into `full_schema_deployment.sql` for fresh installations.

For reference, previous migrations included:
- 002_add_rbac_system_safe.sql
- 003_fix_users_userid.sql
- 004_add_attendance_report_permissions.sql
- 005_add_must_change_password.sql
- 006_create_daily_attendance.sql
- 006_create_settings_table.sql
- 008_create_announcements_table.sql
- 009_create_subjects_table.sql
- 010_create_library_tables.sql
- 011_add_api_tokens.sql
- 011_create_holidays_table.sql
- 012_add_view_users_permission.sql
- add_academic_tables.sql
- add_payment_mode_column.sql

These are now all included in the main deployment schema.
