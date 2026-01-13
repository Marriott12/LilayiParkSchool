# URGENT: Fix Login Issues (Admin & Teachers)

## Problem
Both admin and teacher logins fail with "Invalid username or password" error.

## 🎯 COMPLETE SOLUTION

### Step 1: Run Diagnostic (REQUIRED)

**Upload and access this file in your browser:**
```
https://your-domain.com/diagnose_db.php
```

This will show you exactly what's wrong with:
- Database connection
- Users table structure  
- Password hashes
- Role assignments
- UserRoles table structure

### Step 2: Apply Complete Fix

Based on diagnostic results, run this SQL script:

**Via MySQL Command Line:**
```bash
mysql -u your_user -p lilayiparkschool < database/complete_reset_users.sql
```

**Via phpMyAdmin:**
1. Select `lilayiparkschool` database
2. Click **SQL** tab
3. Copy/paste contents of `database/complete_reset_users.sql`
4. Click **Go**

This will:
- ✅ Fix UserRoles table structure (userID INT)
- ✅ Reset admin password to working hash
- ✅ Create test.teacher user with correct password
- ✅ Assign proper roles to both users
- ✅ Verify everything is working

## Default Credentials After Fix

**Admin:**
- Username: `admin`
- Password: `admin123`

**Test Teacher:**
- Username: `test.teacher`
- Password: `teacher123`

⚠️ **Both passwords are the same hash** - Change after first login!

## Common Issues & Quick Fixes

### Issue 1: Seed Data Not Imported
```sql
-- Check if admin exists
SELECT * FROM Users WHERE username = 'admin';
-- If empty, run: seed_data_deployment.sql
```

### Issue 2: Wrong Database Selected
```sql
-- Check current database
SELECT DATABASE();
-- Should be: lilayiparkschool
USE lilayiparkschool;
```

### Issue 3: UserRoles Type Mismatch
```sql
-- Check userID type
SHOW COLUMNS FROM UserRoles LIKE 'userID';
-- Should be: int(11) or INT
-- If VARCHAR, run: complete_reset_users.sql
```

### Issue 4: No Role Assigned
```sql
-- Check admin role
SELECT u.username, r.roleName 
FROM Users u 
LEFT JOIN UserRoles ur ON u.userID = ur.userID
LEFT JOIN Roles r ON ur.roleID = r.roleID
WHERE u.username = 'admin';
-- Should show: admin | admin
```

### Issue 5: Wrong Password Hash
```sql
-- Update admin password
UPDATE Users 
SET password = '$2y$12$gl.WX8Hppgsup4wcrbtVLub768yjANtWmZ9PJDE0v21PU1DRklfa.'
WHERE username = 'admin';
```

## Verification Steps

After running the fix:

1. **Check via diagnostic page:**
   - Visit `https://your-domain.com/diagnose_db.php`
   - All checks should be green ✅

2. **Test login:**
   - Try: admin / admin123
   - Try: test.teacher / teacher123

3. **Check database manually:**
   ```sql
   -- Verify users exist
   SELECT username, email, isActive FROM Users;
   
   -- Verify roles assigned
   SELECT u.username, r.roleName 
   FROM Users u
   JOIN UserRoles ur ON u.userID = ur.userID
   JOIN Roles r ON ur.roleID = r.roleID;
   ```

## Files Reference

1. **diagnose_db.php** - Upload and run in browser (DIAGNOSTIC TOOL)
2. **database/complete_reset_users.sql** - Complete fix (USE THIS!)
3. **database/fix_userroles_type.sql** - Structure fix only
4. **database/ADMIN_LOGIN_FIX.md** - Detailed troubleshooting

## Still Not Working?

### Enable Debug Mode

Add to `login.php` after line with `Auth::attempt()`:

```php
$result = Auth::attempt($username, $password);

// DEBUG - Remove after fixing
echo "<pre>";
echo "Result: "; var_dump($result);
echo "\nSession: "; var_dump($_SESSION);
echo "\nUser from DB: ";
$stmt = $db->prepare("SELECT * FROM Users WHERE username = ?");
$stmt->execute([$username]);
var_dump($stmt->fetch());
die();
```

### Check Application Files

1. **Database connection (.env):**
   ```
   DB_HOST=your-host
   DB_NAME=lilayiparkschool  ← Must match!
   DB_USER=your-user
   DB_PASS=your-password
   ```

2. **Verify Auth.php exists:**
   - File: `includes/Auth.php`
   - Method: `Auth::attempt()`

3. **Check for errors in logs:**
   - PHP error log
   - Application logs folder

## Delete After Fixing

**Security:** Remove `diagnose_db.php` after troubleshooting!

```bash
rm diagnose_db.php
```

## Contact Support

- **Email:** lilayiparkschool@gmail.com  
- **Phone:** +260973116866

---
**Last Updated:** January 13, 2026  
**Note:** Both admin123 and teacher123 use the same password hash for testing
