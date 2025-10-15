# .env Configuration Template

Since `.env` is in `.gitignore`, create it manually:

```bash
# Copy the example:
cp ".env copy" .env

# Or create new .env with this content:
```

## Minimal .env for CyberTirah Framework

```env
#########################################
# CyberTirah Framework Configuration
#########################################

# Application
APP_NAME="CyberTirah Framework"
APP_ENV=development
APP_DEBUG=true
APP_VERSION=2.0.0

# Framework Root URL (NOT legacy SMS path!)
APP_ROOT_URL=/

# Development
DEV_MODE=1
MEMORY_LIMIT=256M
TIME_LIMIT=300

# Security
USE_SESSION=true
SESSION_NAME=CAFSESSID
SESSION_LIFETIME=7200
FORCE_HTTPS=false

# Database
DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_USERNAME=root
DB_PASSWORD=
DB_DATABASE=ct_frame
DB_PREFIX=
DB_CHARSET=utf8mb4

# Mail
MAIL_DRIVER=smtp
MAIL_HOST=localhost
MAIL_PORT=587
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="CyberTirah Framework"

# Cache & Logging
CACHE_DRIVER=file
LOG_LEVEL=debug

# CORS
CORS_ORIGIN=*

# Timezone
TIMEZONE=UTC

#########################################
# LEGACY SMS COMPATIBILITY (Optional)
#########################################
ENABLE_LEGACY_SMS_PATHS=false
LEGACY_SMS_ROOT_PATH=/SMS
```

## Important Settings

### 1. APP_ROOT_URL
```env
# For root installation (most common)
APP_ROOT_URL=/

# For subdirectory installation
APP_ROOT_URL=/myapp/

# For custom path
APP_ROOT_URL=/frame/
```

### 2. Database
```env
DB_DATABASE=ct_frame    # Change to your database name
DB_USERNAME=root        # Change to your database user
DB_PASSWORD=            # Add your database password
```

### 3. Development vs Production
```env
# Development
APP_ENV=development
APP_DEBUG=true
DEV_MODE=1

# Production
APP_ENV=production
APP_DEBUG=false
DEV_MODE=0
```

### 4. Legacy SMS Mode (Optional)
```env
# Disabled (default - recommended)
ENABLE_LEGACY_SMS_PATHS=false

# Enabled (only if you need old SMS paths)
ENABLE_LEGACY_SMS_PATHS=true
LEGACY_SMS_ROOT_PATH=/SMS
```

## Quick Setup

```bash
# 1. Create .env file
cp ".env copy" .env

# 2. Edit .env
nano .env  # or use your favorite editor

# 3. Set APP_ROOT_URL (most important!)
APP_ROOT_URL=/

# 4. Set database credentials
DB_DATABASE=ct_frame
DB_USERNAME=root
DB_PASSWORD=your_password

# 5. Test
php -S localhost:8000 -t Index
```

## Verification

Test if your .env is loaded correctly:

```php
// In any controller
echo getenv('APP_ROOT_URL');  // Should print: /
echo getenv('DB_DATABASE');   // Should print: ct_frame
```

Or check constants:

```php
echo APP_ROOT_URL;      // Should be: /
echo APP_STORAGE_URL;   // Should be: /storage
echo APP_ADMIN_URL;     // Should be: /admin
```

