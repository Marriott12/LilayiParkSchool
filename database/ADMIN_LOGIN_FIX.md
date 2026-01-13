# Admin Login Troubleshooting Guide

## Issue: "Invalid username or password" error

### 🔴 ROOT CAUSE FOUND

There was a critical data type mismatch in the database schema:
- **Users.userID** is `INT` 
- **UserRoles.userID** was `VARCHAR(10)`

This prevents the admin role from being properly assigned, causing login to fail.

### ✅ COMPLETE FIX (Run on Remote Server)

**Use this script:** `database/fix_userroles_type.sql`

This will:
1. Fix the UserRoles table structure (userID INT instead of VARCHAR)
2. Update admin password to a working hash
3. Ensure admin role is properly assigned

```bash
mysql -u your_user -p lilayiparkschool < database/fix_userroles_type.sql
```

### Quick Fix (Run on Remote Server)

Execute this SQL script on your remote database:

```sql
-- Update admin password
UPDATE Users 
SET password = '$2y$12$gl.WX8Hppgsup4wcrbtVLub768yjANtWmZ9PJDE0v21PU1DRklfa.',
    isActive = 'Y',
    mustChangePassword = 'Y'
WHERE username = 'admin';
```

**Then try logging in with:**
- Username: `admin`
- Password: `admin123`

### Using the Fix Script

1. **Via MySQL Command Line:**
   ```bash
   mysql -u your_user -p lilayiparkschool < database/fix_admin_login.sql
   ```

2. **Via phpMyAdmin:**
   - Select `lilayiparkschool` database
   - Click SQL tab
   - Paste contents of `fix_admin_login.sql`
   - Click Go

### Possible Causes

1. **Seed data wasn't imported**
   - Solution: Run `seed_data_deployment.sql`

2. **Wrong table structure**
   - Check if Users table has these columns: userID, username, email, password, isActive
   - Run: `DESCRIBE Users;`

3. **User is inactive**
   - Run: `SELECT username, isActive FROM Users WHERE username = 'admin';`
   - Should show isActive = 'Y'

4. **Role not assigned**
   - Run: 
     ```sql
     SELECT u.username, r.roleName 
     FROM Users u
     LEFT JOIN UserRoles ur ON u.userID = ur.userID
     LEFT JOIN Roles r ON ur.roleID = r.roleID
     WHERE u.username = 'admin';
     ```
   - Should show roleName = 'admin'

5. **userID type mismatch**
   - Users.userID should be INT AUTO_INCREMENT
   - UserRoles.userID should be VARCHAR(10) that stores the INT as string
   - Run: `SHOW CREATE TABLE Users;` and `SHOW CREATE TABLE UserRoles;`

### Manual Password Reset

If you need to set a different password:

1. **Generate password hash:**
   ```php
   <?php
   echo password_hash('your-new-password', PASSWORD_BCRYPT, ['cost' => 12]);
   ?>
   ```

2. **Update database:**
   ```sql
   UPDATE Users 
   SET password = 'paste-hash-here'
   WHERE username = 'admin';
   ```

### Verification Queries

Run these to diagnose the issue:

```sql
-- 1. Check if admin user exists
SELECT userID, username, email, isActive FROM Users WHERE username = 'admin';

-- 2. Check password hash format
SELECT username, SUBSTRING(password, 1, 7) as hash_prefix, LENGTH(password) as hash_length
FROM Users WHERE username = 'admin';
-- Should show: $2y$12$ and length 60

-- 3. Check role assignment
SELECT u.username, ur.roleID, r.roleName
FROM Users u
LEFT JOIN UserRoles ur ON u.userID = ur.userID
LEFT JOIN Roles r ON ur.roleID = r.roleID
WHERE u.username = 'admin';
-- Should show roleID = R001, roleName = admin

-- 4. Check Users table structure
DESCRIBE Users;
-- Verify: userID INT, username VARCHAR, password VARCHAR(255), isActive ENUM('Y','N')
```

### Check Application Configuration

1. **Database connection in .env:**
   ```
   DB_HOST=your-remote-host
   DB_NAME=lilayiparkschool
   DB_USER=your-db-user
   DB_PASS=your-db-password
   ```

2. **Test database connection:**
   Create a test file `test_db.php`:
   ```php
   <?php
   require_once 'config/database.php';
   echo "Database connected successfully!<br>";
   
   $sql = "SELECT username, email, isActive FROM Users WHERE username = 'admin'";
   $stmt = $db->query($sql);
   $user = $stmt->fetch();
   
   if ($user) {
       echo "Admin user found!<br>";
       echo "Username: " . $user['username'] . "<br>";
       echo "Email: " . $user['email'] . "<br>";
       echo "Active: " . $user['isActive'] . "<br>";
   } else {
       echo "Admin user NOT found in database!";
   }
   ?>
   ```

3. **Check Auth.php login logic:**
   - File: `includes/Auth.php`
   - Method: `Auth::attempt($username, $password)`
   - Verifies: isActive = 'Y' and password_verify()

### Common Mistakes

❌ **Using old password hash from schema.sql**
- Old hash may not match 'admin123'
- Use the hash from fix_admin_login.sql

❌ **Case sensitivity**
- Username is case-sensitive in some MySQL configurations
- Try exact: `admin` (lowercase)

❌ **UserID type mismatch**
- Users table uses INT for userID
- UserRoles expects VARCHAR(10)
- This is a known issue - UserRoles.userID should be INT

❌ **Database not selected**
- Make sure you're working in the `lilayiparkschool` database
- Run: `USE lilayiparkschool;`

### Fix UserID Type Mismatch (If Needed)

If UserRoles.userID is VARCHAR but Users.userID is INT:

```sql
-- First, clear the UserRoles table
TRUNCATE TABLE UserRoles;

-- Modify the column type
ALTER TABLE UserRoles 
MODIFY COLUMN userID INT NOT NULL;

-- Re-insert admin role
INSERT INTO UserRoles (userID, roleID)
SELECT userID, 'R001'
FROM Users
WHERE username = 'admin';
```

### Still Not Working?

1. **Check application logs:**
   - Location: `logs/` folder
   - Look for PHP errors or database connection issues

2. **Enable error display temporarily:**
   In `config/config.php`:
   ```php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```

3. **Check login.php directly:**
   Add debugging after line where Auth::attempt is called:
   ```php
   $result = Auth::attempt($username, $password);
   var_dump($result); // Add this
   exit; // Add this
   ```

4. **Contact support:**
   - Email: lilayiparkschool@gmail.com
   - Phone: +260973116866

## Testing After Fix

1. Navigate to login page
2. Enter:
   - Username: `admin`
   - Password: `admin123`
3. Should login successfully
4. Will be prompted to change password (mustChangePassword = 'Y')
5. Change to a secure password

## Security Note

Always change the default password immediately after logging in!
