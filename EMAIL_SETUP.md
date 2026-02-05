# Email Configuration Guide

## Overview
The Lilayi Park School Management System now supports email notifications for sending account credentials, password resets, and general notifications.

## PHPMailer Installation

The system uses PHPMailer library for sending emails. You need to install it before email functionality will work.

### Method 1: Using Composer (Recommended)

1. Open terminal/command prompt in the project root directory
2. Run:
   ```bash
   composer require phpmailer/phpmailer
   ```

### Method 2: Manual Installation

1. Download PHPMailer from: https://github.com/PHPMailer/PHPMailer/releases
2. Extract the files
3. Copy the `src` folder to `includes/PHPMailer/`
4. Create `includes/PHPMailer.php` with the following content:
   ```php
   <?php
   require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
   require_once __DIR__ . '/PHPMailer/src/SMTP.php';
   require_once __DIR__ . '/PHPMailer/src/Exception.php';
   ```

## SMTP Configuration

### 1. Access Settings Page
- Log in as admin
- Go to Settings > Email/SMTP Configuration

### 2. Common SMTP Settings

#### Gmail
- **SMTP Host:** smtp.gmail.com
- **SMTP Port:** 587
- **Encryption:** TLS
- **Username:** your-email@gmail.com
- **Password:** App-specific password (not your regular Gmail password)

**Gmail App Password Setup:**
1. Go to Google Account settings
2. Security > 2-Step Verification
3. App passwords
4. Generate new app password for "Mail"
5. Use this password in SMTP settings

#### Outlook/Office 365
- **SMTP Host:** smtp.office365.com
- **SMTP Port:** 587
- **Encryption:** TLS
- **Username:** your-email@outlook.com
- **Password:** Your Outlook password

#### Other Providers
Consult your email provider's documentation for SMTP settings.

### 3. Configure Settings

Fill in the following fields:
- **SMTP Host:** Your email provider's SMTP server
- **SMTP Port:** Usually 587 (TLS) or 465 (SSL)
- **Encryption:** TLS or SSL
- **SMTP Username:** Your email address
- **SMTP Password:** Your email password or app-specific password
- **From Email Address:** The email address that will appear as sender
- **From Name:** "Lilayi Park School" (or your school name)
- **Send Account Credentials via Email:** Enable to automatically email login credentials when creating accounts

### 4. Test Configuration

Click "Send Test Email" button to verify your configuration works.

## Features

### Automatic Account Credential Emails
When enabled, the system will automatically send login credentials via email when:
- Creating teacher accounts
- Creating parent accounts
- Resetting passwords

### Email Templates
The system includes pre-formatted email templates for:
- **Account Credentials:** Welcome email with login details
- **Password Reset:** New password notification
- **General Notifications:** Custom notifications

### Security
- Passwords are sent only once upon account creation
- App-specific passwords are recommended for Gmail
- SMTP passwords are stored in database settings
- Users are prompted to change password on first login

## Troubleshooting

### Email Not Sending
1. Verify SMTP settings are correct
2. Check if email notifications are enabled in settings
3. Ensure PHPMailer is installed
4. Check server error logs for detailed error messages
5. Verify firewall allows outbound SMTP connections

### Gmail "Less Secure Apps" Error
Gmail no longer supports "less secure apps". You must:
1. Enable 2-Step Verification
2. Generate and use an App Password

### Port Blocked
If port 587 is blocked:
1. Try port 465 with SSL encryption
2. Contact your hosting provider

## API Usage

### Sending Custom Emails

```php
require_once 'includes/EmailService.php';

$emailService = new EmailService();

// Send test email
$result = $emailService->sendTestEmail('recipient@example.com');

// Send account credentials
$result = $emailService->sendAccountCredentials(
    'user@example.com',
    'username',
    'password',
    'Teacher' // or 'Parent', 'User'
);

// Send custom notification
$result = $emailService->sendNotification(
    'recipient@example.com',
    'Subject Line',
    'Message body',
    ['Key' => 'Value'] // Optional additional info
);

// Check result
if ($result['success']) {
    echo "Email sent successfully!";
} else {
    echo "Error: " . $result['message'];
}
```

## System Requirements
- PHP 7.4 or higher
- PHPMailer library
- Outbound SMTP access (port 587 or 465)
- Valid email account with SMTP access

## Support
For issues with email configuration, contact your system administrator or hosting provider.
