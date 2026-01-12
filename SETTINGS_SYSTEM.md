# Settings System Documentation

## Overview
The Lilayi Park School Management System includes a comprehensive and dynamic settings management system that allows administrators to configure various aspects of the school's operations.

## Database Structure

### Settings Table
```sql
CREATE TABLE settings (
    settingID INT AUTO_INCREMENT PRIMARY KEY,
    settingKey VARCHAR(100) UNIQUE NOT NULL,
    settingValue TEXT NULL,
    category VARCHAR(50) DEFAULT 'general',
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_settingKey (settingKey)
)
```

**Key Features:**
- `settingKey` is UNIQUE to prevent duplicate settings
- `settingValue` stored as TEXT to accommodate various data types
- `category` groups related settings together
- Automatic timestamps track creation and updates
- Indexes optimize lookups by key and category

## Settings Categories

### 1. School Information (`school`)
Basic information about the school:
- `school_name` - Official name of the school
- `school_address` - Physical address
- `school_phone` - Contact phone number
- `school_email` - Official email address
- `school_motto` - School motto or slogan

### 2. Academic Settings (`academic`)
Term and academic year configuration:
- `current_term` - Current term (1, 2, or 3)
- `current_academic_year` - Current academic year (e.g., "2025/2026")
- `term1_start` - Term 1 start date
- `term1_end` - Term 1 end date
- `term2_start` - Term 2 start date
- `term2_end` - Term 2 end date
- `term3_start` - Term 3 start date
- `term3_end` - Term 3 end date
- `attendance_threshold` - Minimum attendance percentage required

### 3. Grading Scale (`grading`)
Define grade boundaries:
- `grade_a_min` - Minimum score for grade A (default: 80)
- `grade_b_min` - Minimum score for grade B (default: 70)
- `grade_c_min` - Minimum score for grade C (default: 60)
- `grade_d_min` - Minimum score for grade D (default: 50)
- `passing_grade` - Minimum passing score (default: 50)

### 4. Financial Settings (`financial`)
Fee and payment configuration:
- `currency` - Currency code (e.g., "ZMW", "USD")
- `late_fee_penalty` - Penalty amount or percentage for late payments

### 5. Library Settings (`library`)
Library module configuration:
- `library_fine_per_day` - Daily fine for overdue books (default: 0.50)
- `library_max_books` - Maximum books a pupil can borrow (default: 3)
- `library_loan_period` - Loan period in days (default: 14)

### 6. Notification Settings (`notifications`)
Control notification delivery:
- `notifications_enabled` - Master toggle for all notifications (0/1)
- `email_notifications` - Enable email notifications (0/1)
- `sms_notifications` - Enable SMS notifications (0/1)
- `sms_api_key` - API key for SMS service provider

### 7. Email/SMTP Configuration (`email`)
Email server settings for outgoing mail:
- `smtp_host` - SMTP server hostname (e.g., "smtp.gmail.com")
- `smtp_port` - SMTP server port (default: 587)
- `smtp_username` - SMTP authentication username
- `smtp_password` - SMTP authentication password
- `smtp_encryption` - Encryption method (tls/ssl)
- `smtp_from_email` - Default sender email address
- `smtp_from_name` - Default sender name
- `send_account_emails` - Auto-send credentials on account creation (0/1)

### 8. Report Card Settings (`reports`)
Configure report card appearance:
- `report_show_position` - Show pupil position/rank (0/1)
- `report_show_average` - Show class average (0/1)
- `report_show_attendance` - Show attendance percentage (0/1)
- `report_head_signature` - Path to headteacher signature image

### 9. System Maintenance (`system`)
System-wide settings:
- `maintenance_mode` - Enable maintenance mode (0/1)
- `session_timeout` - Session timeout in minutes (default: 30)

## SettingsModel API

### Core Methods

#### `getSetting($key, $default = null)`
Retrieve a single setting value.
```php
$schoolName = $settingsModel->getSetting('school_name', 'Default School');
```

#### `setSetting($key, $value, $category = 'general')`
Set or update a setting value. Automatically:
- Updates existing settings
- Creates new settings if they don't exist
- Clears session cache
- Updates the `updatedAt` timestamp

```php
$settingsModel->setSetting('school_name', 'Lilayi Park School', 'school');
```

#### `getAllSettings()`
Get all settings as a key-value array. Results are cached in the session for 5 minutes.
```php
$settings = $settingsModel->getAllSettings();
echo $settings['school_name']; // "Lilayi Park School"
```

#### `getByCategory($category)`
Get all settings in a specific category.
```php
$librarySettings = $settingsModel->getByCategory('library');
```

## Session Caching

The system implements intelligent caching to reduce database queries:

### Cache Methods (in `Session.php`)

#### `cacheSettings($settings)`
Store settings in session cache with 5-minute expiry.
```php
Session::cacheSettings($settings);
```

#### `getCachedSettings()`
Retrieve cached settings if they exist and haven't expired.
```php
$cached = Session::getCachedSettings();
```

#### `clearSettingsCache()`
Clear the settings cache (called automatically when settings change).
```php
Session::clearSettingsCache();
```

### Cache Behavior
- Settings are cached for **5 minutes** (300 seconds)
- Cache is automatically cleared when any setting is updated
- Reduces database load for frequently accessed settings
- First request loads from database, subsequent requests use cache

## Form Validation

The settings form includes comprehensive validation:

### Required Fields
- School name
- Current term (must be 1, 2, or 3)
- Current academic year

### Numeric Validation
All percentage and numeric fields are validated:
- Attendance threshold (0-100)
- Grade boundaries (0-100)
- Late fee penalty
- Library settings
- SMTP port
- Session timeout

### Email Validation
- School email
- SMTP from email

### Error Handling
- Validation errors displayed at the top of the form
- Individual field errors highlighted
- Success message shows count of updated settings
- Database errors caught and displayed gracefully

## Usage Examples

### Retrieving Settings in Code
```php
require_once 'modules/settings/SettingsModel.php';
$settingsModel = new SettingsModel();

// Get single setting
$schoolName = $settingsModel->getSetting('school_name');

// Get with default value
$maxBooks = $settingsModel->getSetting('library_max_books', 3);

// Get all settings
$settings = $settingsModel->getAllSettings();
```

### Using Settings in Views
```php
<h1><?= htmlspecialchars($settings['school_name'] ?? 'School') ?></h1>
<p>Email: <?= htmlspecialchars($settings['school_email']) ?></p>
```

### Updating Settings Programmatically
```php
// Single update
$settingsModel->setSetting('school_name', 'New School Name', 'school');

// Bulk update
$updates = [
    ['key' => 'library_fine_per_day', 'value' => '1.00', 'category' => 'library'],
    ['key' => 'library_max_books', 'value' => '5', 'category' => 'library'],
];

foreach ($updates as $update) {
    $settingsModel->setSetting($update['key'], $update['value'], $update['category']);
}
```

## Migration and Setup

### Initial Setup
Run the migration to create the table and insert default settings:
```bash
mysql -u root -p lilayiparkschool < database/migrations/006_create_settings_table.sql
```

### Verifying Settings
```sql
-- Count settings by category
SELECT category, COUNT(*) as count 
FROM settings 
GROUP BY category 
ORDER BY category;

-- View all settings
SELECT * FROM settings ORDER BY category, settingKey;
```

## Testing

A comprehensive test script is provided to verify settings functionality:

### Running Tests
```bash
cd /path/to/LilayiParkSchool
php test_settings_save.php
```

### Test Coverage
1. **Update Existing Setting** - Verifies existing settings can be updated
2. **Create New Setting** - Tests creating new settings
3. **Bulk Update** - Tests updating multiple settings at once
4. **Cache Clearing** - Verifies cache is cleared on updates
5. **Category Filtering** - Tests retrieving settings by category

All tests should pass, confirming:
- ✓ Settings save correctly to database
- ✓ Cache clearing works properly
- ✓ New settings can be created
- ✓ Updates persist correctly
- ✓ Category filtering functions

## Security Considerations

### CSRF Protection
All settings form submissions are protected with CSRF tokens:
```php
CSRF::validate($_POST['csrf_token']);
```

### Access Control
Only administrators can access settings:
```php
Auth::requireAnyRole(['admin']);
```

### SQL Injection Prevention
All database queries use prepared statements:
```php
$stmt = $this->db->prepare($sql);
$stmt->execute([$value, $key]);
```

### Input Sanitization
- All inputs validated before saving
- Email addresses validated with `FILTER_VALIDATE_EMAIL`
- Numeric fields checked with `is_numeric()`
- Sensitive fields (passwords) stored as-is for proper authentication

## Best Practices

### When Adding New Settings

1. **Choose appropriate category** - Use existing categories or create new ones
2. **Add to migration** - Update `006_create_settings_table.sql`
3. **Add to form** - Update `settings.php` with form field
4. **Add validation** - Include in POST handler validation
5. **Document** - Add to this documentation

### Example: Adding a New Setting
```sql
-- In migration
INSERT IGNORE INTO settings (settingKey, settingValue, category) VALUES
('new_setting_key', 'default_value', 'category_name');
```

```php
// In settings.php POST handler
$settingsModel->setSetting('new_setting_key', $_POST['new_setting_key'] ?? 'default', 'category');
```

```html
<!-- In settings.php form -->
<div class="mb-3">
    <label for="new_setting_key" class="form-label">New Setting Label</label>
    <input type="text" class="form-control" id="new_setting_key" name="new_setting_key" 
           value="<?= htmlspecialchars($settings['new_setting_key'] ?? 'default') ?>">
</div>
```

## Troubleshooting

### Settings Not Saving
1. Check database connection
2. Verify `settings` table exists: `SHOW TABLES LIKE 'settings';`
3. Check for unique key violations: `SELECT * FROM settings WHERE settingKey = 'key_name';`
4. Review error logs for exceptions

### Cache Issues
1. Clear cache manually: `Session::clearSettingsCache();`
2. Verify cache timeout in `Session.php` (default 300 seconds)
3. Check session storage is working

### Validation Errors
1. Check required fields are filled
2. Verify numeric fields contain valid numbers
3. Confirm email addresses are valid format
4. Review validation rules in POST handler

## Performance Optimization

### Current Optimizations
- Session caching reduces database queries
- Indexed columns speed up lookups
- Unique constraint prevents duplicates
- Prepared statements for security and performance

### Recommendations
- Keep cache timeout at 5 minutes for frequently accessed settings
- Use `getByCategory()` instead of `getAllSettings()` when possible
- Avoid calling `setSetting()` in loops; batch updates when possible

## Future Enhancements

Potential improvements:
- Settings import/export functionality
- Version history for settings changes
- Audit trail showing who changed what and when
- Settings groups with permissions
- API endpoint for mobile app configuration
- Settings backup and restore

## Conclusion

The settings system provides a robust, flexible foundation for managing school configuration. With proper validation, caching, and security measures, it ensures settings are stored reliably and accessed efficiently throughout the application.
