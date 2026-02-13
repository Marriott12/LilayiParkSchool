# Deploy Session Fix to Production

## Critical Files to Upload

Upload these files to lps.envisagezm.com via cPanel File Manager or FTP:

1. **includes/Session.php** - CRITICAL: Fixes infinite loop in session start
2. **includes/Auth.php** - Fixes REQUEST_URI undefined index
3. **login.php** - Cleaned up debug code
4. **index.php** - Cleaned up debug code
5. **payments_list.php** - Removed display_errors

## Option 1: cPanel File Manager (Recommended)

1. Log into cPanel at https://lps.envisagezm.com:2083
2. Open File Manager
3. Navigate to `public_html` (or wherever the site is installed)
4. For each file above:
   - Click "Upload" button
   - Select the file from your local: `C:\wamp64\www\LilayiParkSchool\[filename]`
   - Overwrite existing file

## Option 2: Git Pull (if conflicts resolved)

SSH into server and run:
```bash
cd /path/to/lilayiparkschool
git fetch origin
git reset --hard origin/copilot/create-school-management-portal
```

## Option 3: FTP (FileZilla)

1. Connect to lps.envisagezm.com via FTP
2. Navigate to the site directory
3. Upload the 5 files listed above, overwriting existing files

## After Upload

1. Visit: https://lps.envisagezm.com/login.php
2. Login with your credentials
3. Should redirect to dashboard without 500 error

## If still getting 500 error

Check PHP error log in cPanel:
- cPanel → Metrics → Errors
- Look for the latest error after your login attempt
- Share the error message for further diagnosis
