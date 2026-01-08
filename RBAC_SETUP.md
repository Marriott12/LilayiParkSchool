# Role-Based Access Control (RBAC) Setup Guide

## Overview
The Lilayi Park School Management System now includes a complete Role-Based Access Control (RBAC) system that allows:
- Multiple roles per user (e.g., a teacher who is also a parent)
- Granular permissions management
- User account creation integrated with teacher/parent registration
- Admin interface for role assignment

## Database Migration

### Step 1: Run the Migration SQL

You need to run the migration file to add the RBAC tables to your database. Choose one of these methods:

#### Option A: Using phpMyAdmin
1. Open phpMyAdmin in your browser (http://localhost/phpmyadmin)
2. Select the `lilayiparkschool` database
3. Click on the "SQL" tab
4. Open `database/migrations/002_add_rbac_system.sql` in a text editor
5. Copy the entire content
6. Paste it into the SQL query box
7. Click "Go" to execute

#### Option B: Using MySQL Workbench
1. Open MySQL Workbench
2. Connect to your MySQL server
3. Select the `lilayiparkschool` database
4. Click File → Open SQL Script
5. Navigate to `database/migrations/002_add_rbac_system.sql`
6. Click the lightning bolt icon to execute

#### Option C: Using Command Line
If you have MySQL in your PATH:
```bash
mysql -u root lilayiparkschool < database/migrations/002_add_rbac_system.sql
```

### Step 2: Verify Migration

After running the migration, verify that these tables were created:
- `Roles` - Stores role definitions (admin, teacher, parent, etc.)
- `UserRoles` - Links users to their roles (many-to-many)
- `Permissions` - Defines what actions are allowed
- `RolePermissions` - Links roles to permissions

Also verify that Teacher and Parent tables now have a `userID` column.

## Default Credentials

The migration creates a default admin account:
- **Username:** `admin`
- **Password:** `admin123`
- **Email:** `admin@lilayipark.zm`

⚠️ **IMPORTANT:** Change this password immediately after first login!

## System Features

### 1. User Login
- URL: `/modules/auth/login.php`
- Users can login with username or email
- System remembers the page they were trying to access
- Session expires after 2 hours of inactivity

### 2. Default Roles

| Role | Description | Default Permissions |
|------|-------------|---------------------|
| **admin** | System Administrator | Full access to everything |
| **teacher** | Teacher | View pupils, grades, classes; Manage own grades |
| **parent** | Parent/Guardian | View own children's information |
| **accountant** | Accountant | Manage fees and finances |
| **librarian** | Librarian | Manage library resources |

### 3. Creating Users for Teachers/Parents

#### Option A: Create User Account WITH Teacher Record (Recommended)
When adding a new teacher in `teachers_form.php`:
1. Fill in teacher information (name, NRC, SSN, etc.)
2. Check "Create user account for this teacher" (checked by default)
3. System auto-generates username (you can edit it)
4. Click "Generate Password" or enter a custom one
5. Check "Assign Teacher role" (checked by default)
6. Submit the form

The system will:
- Create the teacher record
- Create the user account
- Assign the teacher role
- Link the user to the teacher record
- All in one transaction

#### Option B: Link Existing Teachers to New Accounts
For teachers already in the system:
1. Go to User Management
2. Create a new user account
3. Go to `modules/users/assign_roles.php?id=USER_ID`
4. In the "Teacher/Parent Linking" section
5. Select the teacher from the dropdown
6. Click "Link"

### 4. Multi-Role Users

A user can have multiple roles. For example, a teacher who has a child at the school:

1. Create/edit the user account
2. Go to `modules/users/assign_roles.php?id=USER_ID`
3. Assign the "teacher" role
4. Assign the "parent" role
5. Link to both teacher and parent records

When this user logs in:
- They'll see both teacher and parent menu items
- Session will have both `teacher_id` and `parent_id`
- They can access both modules seamlessly

### 5. Role Management (Admin Only)

URL: `/modules/users/assign_roles.php?id=USER_ID`

Features:
- View user's current roles
- Assign new roles
- Remove roles
- Link to teacher/parent records
- Unlink from teacher/parent records

## Code Usage Examples

### Protecting Pages
```php
<?php
require_once 'includes/Auth.php';

// Require login
Auth::requireLogin();

// Require specific role
Auth::requireRole('admin');

// Require any of these roles
Auth::requireAnyRole(['admin', 'teacher']);
?>
```

### Checking Roles in Code
```php
<?php
// Check if logged in
if (Auth::check()) {
    echo "Welcome " . Auth::username();
}

// Check specific role
if (Auth::isAdmin()) {
    // Show admin menu
}

if (Auth::hasRole('teacher')) {
    // Show teacher-specific content
}

// Get linked teacher ID
$teacherID = Auth::getTeacherID();

// Get linked parent ID
$parentID = Auth::getParentID();
?>
```

### Creating Users Programmatically
```php
<?php
require_once 'modules/users/UsersModel.php';
require_once 'modules/roles/RolesModel.php';

$usersModel = new UsersModel();
$rolesModel = new RolesModel();

// Get role IDs
$teacherRole = $rolesModel->getRoleByName('teacher');
$parentRole = $rolesModel->getRoleByName('parent');

// Create user with multiple roles
$userData = [
    'username' => 'jdoe',
    'email' => 'jdoe@example.com',
    'password' => 'temporaryPassword123',
    'isActive' => 1
];

$roleIDs = [$teacherRole['roleID'], $parentRole['roleID']];
$userID = $usersModel->createWithRoles($userData, $roleIDs);

// Link to teacher record
$usersModel->linkToTeacher($userID, 'TCH001');

// Link to parent record
$usersModel->linkToParent($userID, 'PAR001');
?>
```

## Navigation Updates (TODO)

Update your `navigation.php` to show role-based menus:

```php
<?php if (Auth::hasAnyRole(['admin', 'teacher'])): ?>
    <li><a href="/teachers_list.php">Teachers</a></li>
<?php endif; ?>

<?php if (Auth::isAdmin()): ?>
    <li><a href="/modules/users/users_list.php">User Management</a></li>
<?php endif; ?>

<?php if (Auth::hasRole('parent')): ?>
    <li><a href="/my_children.php">My Children</a></li>
<?php endif; ?>

<li><a href="/modules/auth/logout.php">Logout</a></li>
```

## Security Best Practices

1. **Always use HTTPS** in production
2. **Change default admin password** immediately
3. **Validate user input** before creating accounts
4. **Use strong passwords** (generated ones are recommended)
5. **Regularly audit user roles** and remove unnecessary access
6. **Session timeout** is set to 2 hours - adjust if needed in Auth.php

## Troubleshooting

### Migration Fails
- Check that you're connected to the correct database
- Ensure no tables with the same names exist
- Check MySQL error log for details

### Can't Login
- Verify user account exists in Users table
- Check that user has `isActive = 1`
- Ensure at least one role is assigned
- Try the default admin account first

### Roles Not Working
- Check UserRoles table for the user
- Verify session is storing roles correctly
- Clear browser cookies and try again

### Teacher/Parent Link Not Working
- Ensure userID column exists in Teacher/Parent tables
- Check foreign key constraints are created
- Verify teacher/parent record exists

## Next Steps

1. ✅ Run database migration
2. ✅ Login with default admin account
3. ✅ Change admin password
4. ✅ Create test accounts for teachers/parents
5. ⏳ Update navigation with role-based menus
6. ⏳ Add role protection to all pages
7. ⏳ Enhance parents_form.php with user account creation
8. ⏳ Build user management list page

## Support

For issues or questions:
- Check the commit message for detailed implementation notes
- Review the model files for available methods
- Test with the default admin account first
- Ensure all files are properly required/included

---

**Last Updated:** January 8, 2026  
**Version:** 1.0  
**Migration File:** `database/migrations/002_add_rbac_system.sql`
