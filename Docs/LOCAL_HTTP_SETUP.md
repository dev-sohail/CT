# Local HTTP Setup Guide

## Problem
The framework is forcing HTTPS on local development, causing issues when you want to use HTTP.

## Solution: Configure FORCE_HTTPS

### Quick Fix (Run This)

1. **Run the batch file:**
   ```bash
   create_local_env.bat
   ```
   This will create a `.env` file with `FORCE_HTTPS=false`

### Manual Setup

If you prefer to create `.env` manually:

1. **Copy the template:**
   ```bash
   copy ".env copy" .env
   ```

2. **Edit `.env` and set:**
   ```env
   FORCE_HTTPS=false
   ```
   
3. **Remove or comment out any duplicate `FORCE_HTTPS=true` entries**

### How HTTPS Detection Works

The framework detects HTTPS in `Brain/ct_brain.php` (lines 336-339):

```php
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || filter_var($this->config->get('FORCE_HTTPS', false), FILTER_VALIDATE_BOOLEAN);
```

It checks:
1. Server HTTPS status
2. Port 443 (HTTPS port)
3. X-Forwarded-Proto header (for proxies)
4. **FORCE_HTTPS configuration** ← This is what we control

## Configuration Options

### For Local Development (HTTP)
```env
FORCE_HTTPS=false
# OR leave empty:
FORCE_HTTPS=
```

### For Production (HTTPS)
```env
FORCE_HTTPS=true
```

## Verification

After creating/updating `.env`:

1. **Restart your WAMP server**

2. **Check if HTTP is working:**
   - Visit: `http://localhost/your-path`
   - URLs should show `http://` not `https://`

3. **Verify in PHP:**
   ```php
   // In any controller or test file
   echo getenv('FORCE_HTTPS');  // Should show: false
   echo APP_PROTOCOL;            // Should show: http://
   echo APP_IS_HTTPS ? 'HTTPS' : 'HTTP';  // Should show: HTTP
   ```

## Common Issues

### Issue 1: Still Redirecting to HTTPS
**Solution:** Make sure there's no `.htaccess` file forcing HTTPS redirect

```apache
# Check your .htaccess file and comment out any HTTPS redirect:
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### Issue 2: .env Changes Not Taking Effect
**Solution:** 
1. Clear cache: `Storage/cache/`
2. Restart WAMP server
3. Check if `.env` file exists in root directory

### Issue 3: Session Cookie Secure Flag
If sessions aren't working, check `Brain/Classes/Auth/session.php` (line 26):
```php
'secure' => $isHttps,  // Should be false for HTTP
```

This is automatically handled by the framework.

## File Locations

- **Configuration File:** `.env` (in root directory)
- **HTTPS Detection Logic:** `Brain/ct_brain.php` (lines 336-339)
- **Session Configuration:** `Brain/ct_brain.php` (lines 693-704)
- **URL Helper:** `Brain/Classes/Helpers/url.php`

## Different Environments

### Local Development
```env
APP_ENV=development
APP_DEBUG=true
FORCE_HTTPS=false
APP_URL=http://localhost
```

### Staging/Testing
```env
APP_ENV=staging
APP_DEBUG=true
FORCE_HTTPS=false  # or true depending on your staging setup
APP_URL=http://staging.example.com
```

### Production
```env
APP_ENV=production
APP_DEBUG=false
FORCE_HTTPS=true
APP_URL=https://example.com
```

## Security Note

⚠️ **Important:** Only use HTTP for local development. Always use HTTPS in production for security!

```env
# ✅ Good for local
FORCE_HTTPS=false

# ✅ Good for production
FORCE_HTTPS=true

# ❌ Bad for production
FORCE_HTTPS=false  # DON'T DO THIS IN PRODUCTION!
```

## Related Files

- `.env` - Configuration file (create this)
- `.env copy` - Example configuration
- `create_local_env.bat` - Automated setup script
- `Brain/ct_brain.php` - Framework bootstrap
- `Docs/ENV_TEMPLATE.md` - Configuration documentation

## Date
October 12, 2025

