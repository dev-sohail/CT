# 🔧 Configuration System Documentation

## Overview

The CyberTirah Framework provides a flexible, environment-based configuration system with backward compatibility for legacy projects.

---

## 🏗️ Architecture

### Components

1. **Environment File (`.env`)**: Main configuration storage
2. **MakingEnv Class** (`Brain/ct_brain.php`): Environment loader
3. **LegacyConfig Class** (`Brain/Classes/core/LegacyConfig.php`): Backward compatibility
4. **Legacy Bootstrap** (`config/legacy.php`): Easy inclusion

---

## 📁 File Structure

```
Frame/
├── .env                              # Environment configuration
├── .env.example                      # Example configuration
├── config/
│   └── legacy.php                    # Legacy bootstrap file
├── Brain/
│   ├── ct_brain.php                  # Framework bootstrap with MakingEnv
│   └── Classes/
│       └── core/
│           └── LegacyConfig.php      # Legacy compatibility class
└── Docs/
    └── CONFIGURATION_MIGRATION_GUIDE.md
```

---

## 🎯 Configuration Approaches

### 1. Framework Approach (Recommended for New Code)

```php
<?php
// Modern framework approach
require_once 'Index/index.php';  // Framework bootstrap

// Access via environment
$appName = getenv('APP_NAME');
$dbHost = getenv('DB_HOST');

// Or use Registry
$db = Registry::get('database');
```

### 2. Legacy Approach (For Old SMS Code)

```php
<?php
// Legacy compatibility approach
require_once 'config/legacy.php';

// All old constants work
echo APP_ROOT;
echo APP_VIEWS;

// Database works the same
global $pdo;
$users = $pdo->query("SELECT * FROM users")->fetchAll();
```

### 3. Hybrid Approach (Migration Phase)

```php
<?php
// Use both systems
require_once 'config/legacy.php';

// Old code
include APP_HEADER_FILE;

// New code
$this->load->model('User');
$users = $this->model_user->getAll();

// Old code
include APP_FOOTER_FILE;
```

---

## ⚙️ Environment Variables

### Application Settings

```env
APP_NAME="CyberTirah Framework"
APP_ENV=development              # development, production, testing
APP_DEBUG=true                   # true, false
APP_URL=http://localhost/SMS
APP_VERSION=2.0.0
DEV_MODE=1                       # 1 = On, 0 = Off
```

### Database Configuration

```env
DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=casms
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
DB_PREFIX=
```

### Path Configuration

```env
APP_ROOT_PATH=/SMS
APP_HOST_ROOT=localhost/SMS
```

### Session & Cache

```env
SESSION_DRIVER=file
SESSION_NAME=caframework_session
SESSION_LIFETIME=7200

CACHE_DRIVER=file
CACHE_TTL=3600
ROUTE_CACHE_ENABLED=true
```

### Security

```env
SECURITY_HEADERS=true
FORCE_HTTPS=false
JWT_SECRET=supersecretjwtkey
JWT_TTL=3600
```

---

## 🔑 Defined Constants

### Core Paths

| Constant | Description | Example |
|----------|-------------|---------|
| `APP_ROOT` | Application root directory | `/var/www/html/SMS` |
| `APP_HOST_ROOT` | Host and root path | `localhost/SMS` |
| `ROOT_DIR` | Root with trailing slash | `/var/www/html/SMS/` |

### Module Directories

| Constant | Path |
|----------|------|
| `APP_VIEWS` | `APP_ROOT/views` |
| `APP_STORAGE` | `APP_ROOT/storage` |
| `APP_CONFIG` | `APP_ROOT/config` |
| `APP_CONTROLLERS` | `APP_ROOT/controllers` |
| `APP_MODELS` | `APP_ROOT/models` |
| `APP_SERVICES` | `APP_ROOT/services` |
| `APP_UTILS` | `APP_ROOT/utils` |
| `APP_ROUTES` | `APP_ROOT/routes` |
| `APP_DATABASE` | `APP_ROOT/database` |

### Storage Assets

| Constant | Path |
|----------|------|
| `APP_CSS` | `APP_STORAGE/css` |
| `APP_FONTS` | `APP_STORAGE/fonts` |
| `APP_IMAGES` | `APP_STORAGE/images` |
| `APP_JS` | `APP_STORAGE/js` |

### Layout Directories

| Constant | Path |
|----------|------|
| `APP_LAY` | `APP_VIEWS/layout` |
| `APP_AUTH` | `APP_VIEWS/auth` |
| `APP_PORTALS` | `APP_VIEWS/portals` |
| `APP_SEC` | `APP_VIEWS/sections` |

### Portal Directories

| Constant | Path |
|----------|------|
| `APP_TPORTAL` | `APP_PORTALS/tportal` (Teacher) |
| `APP_SPORTAL` | `APP_PORTALS/sportal` (Student) |
| `APP_STPORTAL` | `APP_PORTALS/stportal` (Staff) |
| `APP_PPORTAL` | `APP_PORTALS/pportal` (Parent) |

### File Paths

| Constant | Path |
|----------|------|
| `APP_HEAD_FILE` | `APP_LAY/head.php` |
| `APP_HEADER_FILE` | `APP_LAY/header.php` |
| `APP_FOOTER_FILE` | `APP_LAY/footer.php` |
| `APP_SCRIPTS_FILE` | `APP_LAY/scripts.php` |
| `APP_CONFIG_FILE` | `APP_CONFIG/config.php` |

### URL Paths (Public-Facing)

| Constant | URL |
|----------|-----|
| `APP_ROOT_URL` | `/SMS` |
| `APP_STORAGE_URL` | `/SMS/storage` |
| `APP_VIEWS_URL` | `/SMS/views` |
| `APP_CCSS_URL` | `/SMS/storage/css` |
| `APP_CJS_URL` | `/SMS/storage/js` |
| `APP_IMAGES_URL` | `/SMS/storage/images` |

### Portal URLs

| Constant | URL |
|----------|-----|
| `APP_TPORTAL_URL` | `/SMS/views/portals/tportal` |
| `APP_SPORTAL_URL` | `/SMS/views/portals/sportal` |
| `APP_STPORTAL_URL` | `/SMS/views/portals/stportal` |
| `APP_PPORTAL_URL` | `/SMS/views/portals/pportal` |
| `APP_ADMIN_URL` | `/SMS/views/portals/sportal/admin` |

---

## 💻 Code Examples

### Example 1: Simple Page

```php
<?php
// index.php
require_once 'config/legacy.php';

// Include header
include APP_HEADER_FILE;
?>

<h1>Welcome to <?= APP_NAME ?></h1>
<p>This is the home page.</p>

<?php
// Include footer
include APP_FOOTER_FILE;
?>
```

### Example 2: Controller with Database

```php
<?php
// controllers/StudentController.php
require_once __DIR__ . '/../config/legacy.php';

class StudentController
{
    private $db;
    
    public function __construct()
    {
        global $pdo;
        $this->db = $pdo;
    }
    
    public function listStudents()
    {
        $stmt = $this->db->query("SELECT * FROM students ORDER BY name");
        $students = $stmt->fetchAll();
        
        include APP_HEADER_FILE;
        include APP_VIEWS . '/students/list.php';
        include APP_FOOTER_FILE;
    }
    
    public function getStudent($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
```

### Example 3: Portal Page

```php
<?php
// views/portals/tportal/dashboard.php
require_once __DIR__ . '/../../../config/legacy.php';

// Check authentication
if (!isset($_SESSION['teacher_id'])) {
    header('Location: ' . APP_AUTH_URL . '/login.php');
    exit;
}

// Include teacher menu
include APP_TPORTAL_MENU;

// Get teacher data
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM teachers WHERE id = ?");
$stmt->execute([$_SESSION['teacher_id']]);
$teacher = $stmt->fetch();
?>

<h1>Teacher Dashboard</h1>
<p>Welcome, <?= htmlspecialchars($teacher['name']) ?>!</p>

<link rel="stylesheet" href="<?= APP_CCSS_URL ?>/custom-style.css">
<script src="<?= APP_CJS_URL ?>/custom.js"></script>
```

### Example 4: API Endpoint

```php
<?php
// api/students.php
require_once __DIR__ . '/../config/legacy.php';

header('Content-Type: application/json');

global $pdo;

try {
    $stmt = $pdo->query("SELECT id, name, email FROM students");
    $students = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $students,
        'count' => count($students)
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);
}
```

---

## 🔍 Debugging

### Check Configuration Status

```php
<?php
require_once 'config/legacy.php';

// Check if constants are defined
var_dump(defined('APP_ROOT'));
var_dump(defined('APP_VIEWS'));

// Check database
var_dump($pdo instanceof PDO);

// Check session
var_dump(session_status() === PHP_SESSION_ACTIVE);

// Check dev mode
var_dump($devmod);
```

### Test Script

Run the test script to verify everything:
```bash
php test_legacy_config.php
```

Or access via browser:
```
http://localhost/SMS/test_legacy_config.php
```

---

## 🛠️ Advanced Usage

### Get Configuration Programmatically

```php
<?php
$config = LegacyConfig::getInstance();

// Get values
$devMode = $config->get('devmod');
$dbHost = $config->get('db.host');
$dbName = $config->get('db.database');

// Get PDO instance
$pdo = $config->getPdo();
```

### Custom Environment Variables

Add to `.env`:
```env
CUSTOM_API_KEY=your_api_key
CUSTOM_FEATURE=enabled
```

Access in code:
```php
$apiKey = getenv('CUSTOM_API_KEY');
$featureEnabled = getenv('CUSTOM_FEATURE') === 'enabled';
```

---

## 🔒 Security Best Practices

1. **Never commit `.env` file to version control**
   ```bash
   echo ".env" >> .gitignore
   ```

2. **Use `.env.example` for documentation**
   ```bash
   cp .env .env.example
   # Remove sensitive values from .env.example
   ```

3. **Set proper file permissions**
   ```bash
   chmod 600 .env
   ```

4. **Validate configuration in production**
   ```php
   if (getenv('APP_ENV') === 'production') {
       if (!getenv('DB_PASSWORD')) {
           die('Database password required in production!');
       }
   }
   ```

---

## 📊 Configuration Checklist

- [ ] `.env` file created and configured
- [ ] Database credentials set
- [ ] `APP_ROOT_PATH` matches your project
- [ ] `DEV_MODE` set correctly (1 for dev, 0 for prod)
- [ ] `config/legacy.php` included in legacy files
- [ ] Test script runs successfully
- [ ] All constants defined
- [ ] Database connection works
- [ ] Session management active
- [ ] File paths correct
- [ ] URL paths accessible

---

## 🚀 Performance Tips

1. **Cache configuration in production**
2. **Use opcache for PHP files**
3. **Minimize environment variable reads**
4. **Pre-define frequently used paths**

---

## 📚 Related Documentation

- [Configuration Migration Guide](CONFIGURATION_MIGRATION_GUIDE.md)
- [Framework Architecture](.cursor/rules/cybertirah-architecture.mdc)
- [Module Development](.cursor/rules/module-development.mdc)
- [Database Patterns](database-patterns.mdc)

---

## ✅ Summary

The CyberTirah configuration system provides:

- ✅ **Environment-based** configuration
- ✅ **Backward compatibility** with old code
- ✅ **Secure** credential management
- ✅ **Flexible** path configuration
- ✅ **Easy migration** from old systems
- ✅ **Complete documentation**

---

**File**: `Docs/CONFIGURATION_SYSTEM.md`  
**Version**: 2.0.0  
**Updated**: October 12, 2025

