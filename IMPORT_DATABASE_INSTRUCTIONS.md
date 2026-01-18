# Database Import Instructions for envithcy_lps

## Your Situation
✅ Database exists: `envithcy_lps`  
✅ Connection working  
❌ No tables created yet

## Step-by-Step Fix

### Method 1: Using phpMyAdmin (Easiest)

1. **Login to phpMyAdmin** (usually at your-domain.com/phpmyadmin or cpanel)

2. **Select Database**
   - Click on `envithcy_lps` database in left sidebar

3. **Import Schema** (First!)
   - Click **Import** tab at the top
   - Click **Choose File**
   - Select `full_schema_deployment.sql` from your computer
   - Scroll down and click **Go**
   - Wait for success message (should create 26 tables)

4. **Import Seed Data** (Second!)
   - Still in `envithcy_lps` database
   - Click **Import** tab again
   - Click **Choose File**
   - Select `seed_data_deployment.sql`
   - Click **Go**
   - Wait for success message (creates admin user, roles, permissions, etc.)

5. **Verify**
   - Click **Structure** tab
   - You should see 26 tables listed
   - Refresh `diagnose_db.php` - all checks should be green!

### Method 2: Using cPanel File Manager + phpMyAdmin

1. **Upload SQL files to your server:**
   - Login to cPanel
   - Open File Manager
   - Navigate to a temporary folder (or public_html)
   - Upload both:
     - `full_schema_deployment.sql`
     - `seed_data_deployment.sql`

2. **Import via phpMyAdmin:**
   - Follow Method 1 steps above
   - Or browse to the uploaded files when importing

### Method 3: Using MySQL Command Line (If you have SSH)

```bash
# Navigate to where you uploaded the SQL files
cd /path/to/sql/files

# Import schema
mysql -u your_username -p envithcy_lps < full_schema_deployment.sql

# Import seed data
mysql -u your_username -p envithcy_lps < seed_data_deployment.sql
```

## Important Notes

1. **Import ORDER matters!**
   - `full_schema_deployment.sql` FIRST (creates tables)
   - `seed_data_deployment.sql` SECOND (adds data)

2. **File locations on your computer:**
   - Look in your downloaded GitHub repository
   - Folder: `LilayiParkSchool/database/`
   - Files you need:
     - `full_schema_deployment.sql`
     - `seed_data_deployment.sql`

3. **Database name:**
   - Your database is `envithcy_lps` (not lilayiparkschool)
   - Make sure `.env` file has this correct:
     ```
     DB_NAME=envithcy_lps
     ```

## After Import

1. **Refresh diagnose_db.php** - Should show:
   - ✅ 26 tables exist
   - ✅ Admin user found
   - ✅ Roles assigned
   - ✅ Password verified

2. **Login credentials:**
   - Username: `admin`
   - Password: `admin123`

3. **Delete diagnose_db.php** for security:
   ```bash
   # Via file manager or SSH
   rm diagnose_db.php
   ```

## Common Import Errors

### "MySQL server has gone away"
- File too large for PHP upload limit
- Solution: Use SSH method or increase PHP upload limits in cPanel

### "Access denied"
- Database user doesn't have CREATE TABLE permission
- Solution: Grant ALL privileges to database user in cPanel

### "Table already exists"
- Some tables were partially created
- Solution: Drop all tables first, then re-import
  ```sql
  DROP DATABASE envithcy_lps;
  CREATE DATABASE envithcy_lps;
  ```

## Need Help?

If import fails, take a screenshot of the error and check:
- PHP error log
- MySQL error log
- phpMyAdmin error message

---
**Next:** After successful import, login and change the default admin password!
