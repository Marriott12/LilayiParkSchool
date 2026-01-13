# URGENT: Fix Admin Login on Remote Server

## Problem
Admin login fails with "Invalid username or password" error.

## Root Cause
Critical database schema bug: UserRoles.userID was VARCHAR but should be INT to match Users.userID.

## Solution (Choose One)

### Option 1: Run Complete Fix Script (RECOMMENDED)
```bash
mysql -u your_user -p lilayiparkschool < database/fix_userroles_type.sql
```

This fixes:
- ✅ UserRoles table structure
- ✅ Admin password 
- ✅ Admin role assignment

### Option 2: Manual Fix via phpMyAdmin
1. Select `lilayiparkschool` database
2. Click **SQL** tab
3. Copy and paste the contents of `database/fix_userroles_type.sql`
4. Click **Go**

### Option 3: Quick SQL Commands

**If you just need to reset password:**
```sql
UPDATE Users 
SET password = '$2y$12$gl.WX8Hppgsup4wcrbtVLub768yjANtWmZ9PJDE0v21PU1DRklfa.',
    isActive = 'Y'
WHERE username = 'admin';

INSERT INTO UserRoles (userID, roleID)
SELECT userID, 'R001' FROM Users WHERE username = 'admin'
ON DUPLICATE KEY UPDATE roleID = 'R001';
```

## After Running Fix

**Login credentials:**
- Username: `admin`
- Password: `admin123`

⚠️ Change password immediately after first login!

## Verification

Run this query to verify:
```sql
SELECT u.username, u.email, u.isActive, r.roleName
FROM Users u
LEFT JOIN UserRoles ur ON u.userID = ur.userID
LEFT JOIN Roles r ON ur.roleID = r.roleID
WHERE u.username = 'admin';
```

**Expected result:**
- username: admin
- isActive: Y
- roleName: admin

## Files Reference
- `database/fix_userroles_type.sql` - Complete fix (use this!)
- `database/fix_admin_login.sql` - Password reset only
- `database/ADMIN_LOGIN_FIX.md` - Full troubleshooting guide

## Still Not Working?
Check [database/ADMIN_LOGIN_FIX.md](database/ADMIN_LOGIN_FIX.md) for detailed troubleshooting.

---
**Last Updated:** January 13, 2026
