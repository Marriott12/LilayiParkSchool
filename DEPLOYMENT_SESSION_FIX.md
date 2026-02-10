# Session Cookie Fix - Deployment Guide

## Problem
Session cookies (PHPSESSID) not persisting on production server due to server-level output buffering preventing Set-Cookie headers from being sent to browser.

## Root Cause
Production server has `output_buffering = On` at PHP.ini level which cannot be overridden by application code. The Set-Cookie header is being buffered and not reaching the browser.

## Solution
Instead of trying to disable output buffering (which the server doesn't allow), we **flush the buffer** immediately after `session_start()` to force the Set-Cookie header to be sent.

## Files to Deploy

### 1. includes/Session.php (CRITICAL)
Modified `Session::start()` to flush output buffer after `session_start()`:
```php
// CRITICAL: Flush output buffer to force session cookie to be sent
if (ob_get_level() > 0) {
    @ob_flush();
    @flush();
}
```

### 2. login.php
Removed buffer-closing code that was causing HTTP 500 errors.

### 3. includes/bootstrap.php
Removed buffer-closing code to prevent server errors.

### 4. .htaccess
Added `php_flag output_buffering Off` to both PHP 7 and PHP 8 sections (may not work on all servers, but doesn't hurt).

### 5. php.ini (NEW FILE)
Created local php.ini with `output_buffering = Off` for FastCGI/cPanel environments.

### 6. config/config.php
Session configuration with output buffering settings (won't override server, but documents intent).

### 7. test_force_cookie.php (for testing only)
Updated to flush buffer for accurate testing.

## Deployment Steps

1. **Backup production files first**
   ```bash
   # On production server
   cp includes/Session.php includes/Session.php.backup
   cp login.php login.php.backup
   cp includes/bootstrap.php includes/bootstrap.php.backup
   ```

2. **Upload modified files via FileZilla/cPanel File Manager:**
   - includes/Session.php
   - login.php
   - includes/bootstrap.php
   - .htaccess
   - php.ini
   - config/config.php
   - test_force_cookie.php

3. **Test session fix:**
   ```
   https://lps.envisagezm.com/test_force_cookie.php
   ```
   
   Expected results:
   - Counter should **increase** on each refresh (1, 2, 3, 4...)
   - PHPSESSID cookie should appear in $_COOKIE array
   - Output buffering level doesn't matter as long as counter increases

4. **Test login:**
   ```
   https://lps.envisagezm.com/login.php
   ```
   
   Login with: `admin` / `admin123`
   
   Expected: Redirect to dashboard without infinite loop

5. **Verify session persistence:**
   - After login, navigate to different pages
   - Session should remain active
   - Logout should work properly

6. **Clean up test files:**
   ```bash
   rm test_force_cookie.php
   rm test_standalone_session.php
   rm test_simple_session.php
   rm test_cookie_debug.php
   # ... delete all test_*.php files
   ```

## Technical Details

### Why This Works
- Server forces output buffering ON at level 1
- We can't disable it, but we CAN flush it
- `ob_flush()` sends buffered content (including headers) to the browser
- This makes the Set-Cookie header reach the browser
- Browser then stores PHPSESSID cookie and sends it back on next request

### Why Previous Attempts Failed
- `ini_set('output_buffering', 'Off')` - Server ignores this
- `ob_end_clean()` - Can't close server-level buffer, causes 500 error
- `.htaccess php_flag` - Many servers ignore this in FastCGI mode

### The Key Code
```php
session_start();

// CRITICAL: Flush output buffer to force session cookie to be sent
if (ob_get_level() > 0) {
    @ob_flush();  // Flush PHP's internal buffer
    @flush();     // Flush system output buffer
}
```

This is called in `Session::start()` which runs on every page load via bootstrap.php.

## Rollback Plan

If this causes issues:

1. Restore backed-up files:
   ```bash
   cp includes/Session.php.backup includes/Session.php
   cp login.php.backup login.php
   cp includes/bootstrap.php.backup includes/bootstrap.php
   ```

2. Remove php.ini if it causes problems

3. Contact Hostinger support about session cookie issues

## Success Criteria

✅ test_force_cookie.php counter increases on refresh  
✅ Login works without infinite loop  
✅ Sessions persist across page loads  
✅ No HTTP 500 errors  
✅ Payment form remains working (classID fix from earlier)

## Notes

- Regular cookies (via `setcookie()`) work fine - only session cookies were affected
- The payment form fix (moving classID field) is separate and should still work
- Output buffering level 1 will remain active, but flushing makes sessions work anyway
