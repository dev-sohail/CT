# ✅ APP_ROOT_URL Fix - Complete

## 🎯 Problem Identified

**Issue**: `APP_ROOT_URL` was being set to the old SMS project path (`/SMS`) instead of the framework's actual root URL.

**Impact**: 
- Framework routes would generate incorrect URLs
- Links would point to `/SMS/...` instead of `/...`
- Confusion between framework paths and legacy project paths

---

## ✅ Solution Implemented

### 1. **Updated `Brain/ct_brain.php`**

#### Old Code (Wrong):
```php
$rootPath = $this->config->get('APP_ROOT_PATH', '/SMS');
$this->defineIfNotExists('APP_ROOT_URL', $rootPath); // ❌ Wrong!
```

#### New Code (Correct):
```php
// Framework root URL (not legacy path)
$frameworkRoot = $this->config->get('APP_ROOT_URL', '/');
$this->defineIfNotExists('APP_ROOT_URL', $frameworkRoot); // ✅ Correct!

// Framework standard URLs
$this->defineIfNotExists('APP_STORAGE_URL', $frameworkRoot . 'storage');
$this->defineIfNotExists('APP_ADMIN_URL', $frameworkRoot . 'admin');

// Legacy SMS paths (only if explicitly enabled)
$enableLegacy = filter_var($this->config->get('ENABLE_LEGACY_SMS_PATHS', false), FILTER_VALIDATE_BOOLEAN);

if ($enableLegacy) {
    // Define LEGACY_* constants for backward compatibility
    // LEGACY_ROOT, LEGACY_VIEWS, LEGACY_STORAGE, etc.
}
```

---

## 📝 Key Changes

### Framework Constants (Always Defined)
```php
APP_ROOT_URL      = '/'              ← Framework root (from .env)
APP_STORAGE_URL   = '/storage'       ← Framework storage
APP_ADMIN_URL     = '/admin'         ← Framework admin
```

### Legacy Constants (Optional - Only if ENABLE_LEGACY_SMS_PATHS=true)
```php
LEGACY_ROOT              ← Old SMS project root
LEGACY_ROOT_URL          ← Old SMS URL path
LEGACY_VIEWS             ← Old views directory
LEGACY_STORAGE           ← Old storage directory
LEGACY_PORTALS           ← Old portals
LEGACY_TPORTAL_URL       ← Teacher portal URL
LEGACY_SPORTAL_URL       ← Staff portal URL
LEGACY_STPORTAL_URL      ← Student portal URL
LEGACY_PPORTAL_URL       ← Parent portal URL
```

---

## 🔧 Configuration (.env)

### Created New `.env` File

```env
# Framework Root URL (NOT legacy SMS path!)
APP_ROOT_URL=/

# For subdirectory installations:
# APP_ROOT_URL=/subdirectory/

#########################################
# LEGACY SMS COMPATIBILITY (Optional)
#########################################
ENABLE_LEGACY_SMS_PATHS=false
LEGACY_SMS_ROOT_PATH=/SMS
```

---

## 📊 Before vs After

### Before (Wrong)
```
Framework at: http://frame.ct.com/
But APP_ROOT_URL = '/SMS'

Result:
- Home: /SMS/           ❌ Wrong
- Admin: /SMS/admin     ❌ Wrong
- API: /SMS/api         ❌ Wrong
- Storage: /SMS/storage ❌ Wrong
```

### After (Correct)
```
Framework at: http://frame.ct.com/
And APP_ROOT_URL = '/'

Result:
- Home: /               ✅ Correct
- Admin: /admin         ✅ Correct
- API: /api             ✅ Correct
- Storage: /storage     ✅ Correct
```

---

## 🔄 Migration Path

### For CyberTirah Framework Users
**No action needed!** The framework now uses the correct root URL by default.

```env
# Just set this in .env:
APP_ROOT_URL=/
```

### For Legacy SMS Project Users
If you still need the old SMS paths:

```env
# Enable legacy mode:
ENABLE_LEGACY_SMS_PATHS=true
LEGACY_SMS_ROOT_PATH=/SMS
```

Then use the `LEGACY_*` constants in your old code:
```php
// Old code can still work:
$url = LEGACY_ROOT_URL . '/views/auth/login.php';

// Framework uses:
$url = APP_ROOT_URL . 'login';
```

---

## ✅ Benefits

### 1. **Correct URL Generation**
```php
// Now generates correct URLs
Router::url('home')         → '/'
Router::url('admin.home')   → '/admin'
Router::url('api.health')   → '/api/health'
```

### 2. **Clear Separation**
- Framework constants: `APP_*`
- Legacy constants: `LEGACY_*`
- No confusion between the two

### 3. **Backward Compatible**
- Old SMS code can still work with `LEGACY_*` constants
- Framework code uses `APP_*` constants
- Both can coexist if needed

### 4. **Flexible Installation**
```env
# Root installation
APP_ROOT_URL=/

# Subdirectory installation
APP_ROOT_URL=/myapp/

# Custom domain
APP_ROOT_URL=/
```

---

## 🧪 Testing

### Verify Framework URLs
```php
// Check constants
echo APP_ROOT_URL;      // Should be: /
echo APP_STORAGE_URL;   // Should be: /storage
echo APP_ADMIN_URL;     // Should be: /admin

// Test routes
Router::url('home')           // Should be: /
Router::url('blog.show', ['id' => 1])  // Should be: /blog/1
```

### Verify Legacy (if enabled)
```php
// Only if ENABLE_LEGACY_SMS_PATHS=true
echo LEGACY_ROOT_URL;   // Should be: /SMS
echo defined('LEGACY_VIEWS') ? 'Defined' : 'Not defined';
```

---

## 📚 Usage Examples

### In Controllers
```php
class BlogController extends Controller
{
    public function index(): void
    {
        $data = [
            'homeUrl' => Router::url('home'),           // Uses APP_ROOT_URL
            'adminUrl' => APP_ADMIN_URL,                // Uses APP_ADMIN_URL
            'storageUrl' => APP_STORAGE_URL             // Uses APP_STORAGE_URL
        ];
        
        $this->load->view('public/Blog/index', $data);
    }
}
```

### In Views
```php
<!-- Correct URLs -->
<a href="<?= Router::url('home') ?>">Home</a>
<a href="<?= APP_ADMIN_URL ?>">Admin</a>
<img src="<?= APP_STORAGE_URL ?>/images/logo.png">

<!-- Legacy URLs (only if enabled) -->
<?php if (defined('LEGACY_ROOT_URL')): ?>
    <a href="<?= LEGACY_ROOT_URL ?>">Old SMS System</a>
<?php endif; ?>
```

### In JavaScript
```javascript
// Pass from PHP to JS
const appConfig = {
    rootUrl: '<?= APP_ROOT_URL ?>',
    apiUrl: '<?= APP_ROOT_URL ?>api/',
    storageUrl: '<?= APP_STORAGE_URL ?>/'
};

// Use in AJAX calls
fetch(appConfig.apiUrl + 'health')
    .then(response => response.json())
    .then(data => console.log(data));
```

---

## 📋 Files Modified

1. **Brain/ct_brain.php** - Updated `defineLegacyConstants()` method
2. **.env** - Created with correct `APP_ROOT_URL=/`
3. **URL_FIX_SUMMARY.md** - This documentation

---

## ✅ Verification Checklist

- [x] `APP_ROOT_URL` defaults to `/` (not `/SMS`)
- [x] Framework URLs use `APP_*` constants
- [x] Legacy SMS paths renamed to `LEGACY_*`
- [x] Legacy mode is optional (disabled by default)
- [x] `.env` file created with correct settings
- [x] Syntax verified (no errors)
- [x] Documentation updated
- [x] Backward compatibility maintained

---

## 🎯 Result

**Status**: ✅ **Fixed**

- **APP_ROOT_URL** now correctly uses framework's root URL
- **Legacy SMS paths** separated as `LEGACY_*` constants
- **Clear distinction** between framework and legacy
- **Backward compatible** when needed
- **Flexible** for any installation type

---

**Updated By**: CyberTirah Framework Team  
**Date**: October 12, 2025  
**Version**: 2.0.0  
**Issue**: APP_ROOT_URL incorrectly pointing to legacy SMS path  
**Status**: 🟢 **Resolved**

