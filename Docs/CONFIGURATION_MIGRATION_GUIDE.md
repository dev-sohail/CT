# 📋 Configuration Migration Guide

## Overview

This guide helps you migrate from the old SMS project configuration to the modern CyberTirah Framework configuration system.

---

## 🔄 Migration Path

### Old System (config.php)
```php
// Old config.php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "casms";

$pdo = new PDO($dsn, $username, $password, $options);

define('APP_ROOT', $_SERVER["DOCUMENT_ROOT"] . '/SMS');
define('APP_VIEWS', APP_ROOT . '/views');
// ... many more defines
```

### New System (Framework + .env)
```php
// Brain/Classes/core/LegacyConfig.php handles everything
require_once 'config/legacy.php';

// All constants are automatically defined
// $pdo is available globally
// $devmod is set from environment
```

---

## 🛠️ Setup Instructions

### Step 1: Update `.env` File

Add these SMS-specific settings to your `.env`:

```env
# =========================================
# APPLICATION SETTINGS
# =========================================
APP_NAME="CyberTirah Framework"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost/SMS
DEV_MODE=1

# =========================================
# DATABASE CONFIGURATION
# =========================================
DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=casms
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4

# =========================================
# PATH CONFIGURATION (SMS Project)
# =========================================
APP_ROOT_PATH=/SMS
APP_HOST_ROOT=localhost/SMS
```

### Step 2: Replace Old config.php

**Old way**:
```php
require_once 'config/config.php';
```

**New way**:
```php
require_once 'config/legacy.php';
```

### Step 3: Verify Constants

All these constants are automatically defined:

#### **File System Paths**
```php
APP_ROOT           // /var/www/html/SMS
APP_HOST_ROOT      // localhost/SMS
ROOT_DIR           // /var/www/html/SMS/

// Directories
APP_VIEWS          // /var/www/html/SMS/views
APP_STORAGE        // /var/www/html/SMS/storage
APP_CONFIG         // /var/www/html/SMS/config
APP_CONTROLLERS    // /var/www/html/SMS/controllers
APP_MODELS         // /var/www/html/SMS/models
APP_SERVICES       // /var/www/html/SMS/services
APP_UTILS          // /var/www/html/SMS/utils
APP_ROUTES         // /var/www/html/SMS/routes
APP_DATABASE       // /var/www/html/SMS/database

// Storage Assets
APP_CSS            // /var/www/html/SMS/storage/css
APP_FONTS          // /var/www/html/SMS/storage/fonts
APP_IMAGES         // /var/www/html/SMS/storage/images
APP_JS             // /var/www/html/SMS/storage/js

// Layout
APP_LAY            // /var/www/html/SMS/views/layout
APP_AUTH           // /var/www/html/SMS/views/auth
APP_PORTALS        // /var/www/html/SMS/views/portals
APP_SEC            // /var/www/html/SMS/views/sections

// Portals
APP_TPORTAL        // /var/www/html/SMS/views/portals/tportal
APP_SPORTAL        // /var/www/html/SMS/views/portals/sportal
APP_STPORTAL       // /var/www/html/SMS/views/portals/stportal
APP_PPORTAL        // /var/www/html/SMS/views/portals/pportal
```

#### **File Paths**
```php
APP_SCRIPTS_FILE   // /var/www/html/SMS/views/layout/scripts.php
APP_HEAD_FILE      // /var/www/html/SMS/views/layout/head.php
APP_HEADER_FILE    // /var/www/html/SMS/views/layout/header.php
APP_FOOTER_FILE    // /var/www/html/SMS/views/layout/footer.php
APP_CONN_FILE      // /var/www/html/SMS/config/conn.php
APP_CONFIG_FILE    // /var/www/html/SMS/config/config.php
APP_CCSS_FILE      // /SMS/storage/css/custom-style.css
```

#### **Section Files**
```php
APP_SEC_INTRO_FILE // /var/www/html/SMS/views/sections/intro.php
APP_SEC_CP_FILE    // /var/www/html/SMS/views/sections/port.php
APP_SEC_QUO_FILE   // /var/www/html/SMS/views/sections/quote.php
APP_SEC_FEAT_FILE  // /var/www/html/SMS/views/sections/features.php
APP_SEC_REVI_FILE  // /var/www/html/SMS/views/sections/review.php
APP_SEC_CONT_FILE  // /var/www/html/SMS/views/sections/contact.php
```

#### **Database Files**
```php
APP_DB             // /var/www/html/SMS/database/caschool.sql
APP_DB_MIGRATIONS  // /var/www/html/SMS/database/migrations
```

#### **URL Paths (Public-Facing)**
```php
APP_ROOT_URL       // /SMS
APP_STORAGE_URL    // /SMS/storage
APP_VIEWS_URL      // /SMS/views
APP_AUTH_URL       // /SMS/views/auth
APP_LAY_URL        // /SMS/views/layout
APP_PAGES_URL      // /SMS/views/pages
APP_SEC_URL        // /SMS/views/sections
APP_CCSS_URL       // /SMS/storage/css
APP_CJS_URL        // /SMS/storage/js
APP_IMAGES_URL     // /SMS/storage/images

// Portal URLs
APP_PORTALS_URL    // /SMS/views/portals
APP_TPORTAL_URL    // /SMS/views/portals/tportal
APP_SPORTAL_URL    // /SMS/views/portals/sportal
APP_STPORTAL_URL   // /SMS/views/portals/stportal
APP_PPORTAL_URL    // /SMS/views/portals/pportal
APP_ADMIN_URL      // /SMS/views/portals/sportal/admin
```

#### **Layout URLs**
```php
APP_SCRIPTS_URL    // /SMS/views/layout/scripts.php
APP_HEAD_URL       // /SMS/views/layout/head.php
APP_HEADER_URL     // /SMS/views/layout/header.php
APP_FOOTER_URL     // /SMS/views/layout/footer.php
```

#### **Menu Files**
```php
APP_TPORTAL_MENU   // /var/www/html/SMS/views/portals/tportal/menu.php
APP_STPORTAL_MENU  // /var/www/html/SMS/views/portals/stportal/menu.php
APP_SPORTAL_MENU   // /var/www/html/SMS/views/portals/sportal/menu.php
APP_PPORTAL_MENU   // /var/www/html/SMS/views/portals/pportal/menu.php
APP_ADMIN_MENU     // /var/www/html/SMS/views/portals/sportal/admin/menu.php
```

---

## 🔗 Database Connection

### Old Way
```php
global $pdo;
$pdo->query("SELECT * FROM users");
```

### New Way (Same!)
```php
// Still works the same way
global $pdo;
$pdo->query("SELECT * FROM users");

// Or use framework Database class
$db = Registry::get('database');
$db->query("SELECT * FROM users");
```

---

## 📝 Usage Examples

### Example 1: Using in Header File

```php
<?php
// views/layout/header.php
require_once __DIR__ . '/../../config/legacy.php';

// Now all constants are available
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="<?= APP_CCSS_URL ?>/custom-style.css">
    <script src="<?= APP_CJS_URL ?>/custom.js"></script>
</head>
```

### Example 2: Using in Controller

```php
<?php
// controllers/StudentController.php
require_once 'config/legacy.php';

class StudentController
{
    private $pdo;
    
    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    public function index()
    {
        $students = $this->pdo->query("SELECT * FROM students")->fetchAll();
        require APP_HEADER_FILE;
        require APP_VIEWS . '/students/index.php';
        require APP_FOOTER_FILE;
    }
}
```

### Example 3: Portal Page

```php
<?php
// views/portals/tportal/index.php
require_once __DIR__ . '/../../../config/legacy.php';

// Check session
if (!isset($_SESSION['teacher_id'])) {
    header('Location: ' . APP_AUTH_URL . '/login.php');
    exit;
}

// Include menu
require APP_TPORTAL_MENU;

// Your portal code here
?>
```

---

## 🎯 Benefits of New System

### 1. **Environment-Based Configuration**
```env
# Development
DEV_MODE=1
DB_DATABASE=casms_dev

# Production
DEV_MODE=0
DB_DATABASE=casms_prod
```

### 2. **Centralized Management**
- All configuration in one place (`.env`)
- No hardcoded values
- Easy to change between environments

### 3. **Security**
- `.env` file not in version control
- Database credentials hidden
- Error messages controlled by environment

### 4. **Flexibility**
```php
// Get config values dynamically
$config = LegacyConfig::getInstance();
$dbHost = $config->get('db.host');
$devMode = $config->get('devmod');
```

---

## 🔍 Troubleshooting

### Issue: Constants Not Defined

**Solution**: Make sure you include `config/legacy.php`:
```php
require_once __DIR__ . '/config/legacy.php';
```

### Issue: Database Connection Failed

**Solution**: Check your `.env` file:
```env
DB_HOST=localhost
DB_DATABASE=casms
DB_USERNAME=root
DB_PASSWORD=
```

### Issue: Paths Not Working

**Solution**: Verify `APP_ROOT_PATH` in `.env`:
```env
APP_ROOT_PATH=/SMS
```

---

## 📊 Migration Checklist

- [ ] Update `.env` with SMS settings
- [ ] Create `config/legacy.php` file
- [ ] Replace old `config.php` includes
- [ ] Test database connection
- [ ] Verify all constants are defined
- [ ] Test all portal pages
- [ ] Check file paths work correctly
- [ ] Verify URL paths work correctly
- [ ] Test session management
- [ ] Update error reporting settings

---

## 🚀 Next Steps

1. **Update all files** to use `config/legacy.php`
2. **Test thoroughly** in development environment
3. **Update production** `.env` with correct values
4. **Monitor logs** for any issues
5. **Gradually migrate** to framework patterns

---

## 💡 Gradual Migration Strategy

You can gradually migrate from old patterns to new ones:

### Phase 1: Use Legacy Config (Current)
```php
require_once 'config/legacy.php';
// Old code still works
```

### Phase 2: Mix Old and New
```php
require_once 'config/legacy.php';

// Old way
global $pdo;

// New way (framework)
$this->load->model('Student');
$students = $this->model_student->getAll();
```

### Phase 3: Full Framework (Future)
```php
// Pure framework approach
class StudentController extends Controller
{
    public function index()
    {
        $this->load->model('Student');
        $data['students'] = $this->model_student->getAll();
        $this->load->view('students/index', $data);
    }
}
```

---

## 📚 Additional Resources

- **Framework Documentation**: `README.md`
- **Routing Guide**: `Docs/ROUTING_AND_REGISTRY_GUIDE.md`
- **Module Development**: `Docs/PUBLIC_MODULES_SUMMARY.md`
- **API Reference**: `Docs/api.md`

---

## ✅ Summary

The new configuration system:
- ✅ **Backward compatible** - All old code still works
- ✅ **Environment-based** - Easy to manage different environments
- ✅ **Secure** - Credentials in `.env` file
- ✅ **Centralized** - One place for all configuration
- ✅ **Flexible** - Easy to extend and customize
- ✅ **Modern** - Uses best practices
- ✅ **Documented** - Complete migration guide

---

**File**: `Docs/CONFIGURATION_MIGRATION_GUIDE.md`  
**Version**: 2.0.0  
**Updated**: October 12, 2025

