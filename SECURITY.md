# Security Configuration Guide

## ✅ Security Fixes Applied

### 1. Environment-Aware Error Reporting
- **Development mode**: Shows all errors for debugging
- **Production mode**: Hides errors from visitors, logs to `logs/php-errors.log`
- Configure via `APP_ENV` in `.env` file

### 2. Automatic Secure Session Cookies
- Automatically detects HTTPS and enables secure cookies
- Protects against session hijacking
- Supports proxy/load balancer setups (X-Forwarded-Proto)

### 3. Comprehensive Environment Configuration
- Updated `.env.example` with all available options
- Secure defaults for production deployment
- Clear documentation for each setting

---

## Production Deployment Checklist

### Before Going Live:

1. **Set Production Mode**
   ```env
   APP_ENV=production
   ```

2. **Review Database Credentials**
   ```env
   DB_HOST=localhost
   DB_NAME=lilayiparkschool
   DB_USER=your_db_user
   DB_PASSWORD=strong_password_here
   ```

3. **Enable HTTPS**
   - Obtain SSL certificate (Let's Encrypt, etc.)
   - Configure web server for HTTPS
   - Session cookies will automatically become secure

4. **Configure File Permissions**
   ```bash
   # Make logs directory writable
   chmod 755 logs/
   
   # Make uploads directory writable
   chmod 755 uploads/
   
   # Protect sensitive files
   chmod 600 .env
   chmod 600 config/config.php
   ```

5. **Secure .env File**
   - Ensure `.env` is in `.gitignore` ✅ (already done)
   - Never commit `.env` to version control
   - Keep backups in secure location only

6. **Email Configuration** (Optional)
   - Configure SMTP settings in `.env` or via Settings page
   - Test email delivery
   - Enable account email sending if desired

7. **Regular Security Maintenance**
   - Monitor `logs/php-errors.log` for issues
   - Keep PHP and MySQL updated
   - Review user access regularly
   - Backup database regularly

---

## Environment Variables Reference

### Required Settings
| Variable | Description | Example |
|----------|-------------|---------|
| `APP_ENV` | Environment mode | `production` or `development` |
| `DB_HOST` | Database host | `localhost` |
| `DB_NAME` | Database name | `lilayiparkschool` |
| `DB_USER` | Database username | `root` |
| `DB_PASSWORD` | Database password | `your_password` |

### Optional Settings
| Variable | Description | Default |
|----------|-------------|---------|
| `SMTP_HOST` | Email server | (empty) |
| `SMTP_PORT` | Email port | `587` |
| `SMTP_USERNAME` | Email username | (empty) |
| `SMTP_PASSWORD` | Email password | (empty) |
| `SESSION_TIMEOUT` | Session timeout (seconds) | `1800` (30 min) |
| `MAX_UPLOAD_SIZE` | Max file size (bytes) | `5242880` (5MB) |

---

## Security Features Included

✅ **Password Security**
- BCrypt hashing (cost 12)
- Forced password change on first login
- Password reset functionality

✅ **Session Security**
- HTTP-only cookies (prevents XSS)
- Secure cookies on HTTPS
- Session timeout (30 minutes default)
- CSRF protection on all forms

✅ **Access Control**
- Role-based permissions (Admin, Teacher, Parent)
- Protected routes
- SQL injection prevention (PDO prepared statements)

✅ **Data Validation**
- Input sanitization
- XSS prevention
- Email validation
- Phone number validation

✅ **API Security**
- Bearer token authentication
- Token expiration
- CORS headers configured

---

## Monitoring & Logs

### Error Logs
- Location: `logs/php-errors.log`
- Monitor for security issues, SQL errors, etc.
- Rotate logs periodically

### Application Logs
- Database logs available via MySQL
- Session logs in PHP session storage
- Failed login attempts logged

---

## Quick Reference

### Switch to Production Mode
```env
# In .env file
APP_ENV=production
```

### Switch to Development Mode
```env
# In .env file
APP_ENV=development
```

### Check Current Mode
Look for error display on any page:
- **Errors visible** = Development mode
- **Errors hidden** = Production mode (check logs instead)

---

## Support

For additional security concerns or questions:
1. Review Laravel/PHP security best practices
2. Keep all dependencies updated via Composer
3. Follow OWASP security guidelines
4. Regular security audits recommended

**Last Updated**: January 13, 2026
