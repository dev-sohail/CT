# Configuration

The Frame PHP framework uses a `.ct` file format for configuration. Each environment has its own `.ct` file (e.g., `.ct`, `.ct.local`, `.ct.production`). The framework parses `.ct` files and exposes values as PHP constants.

## 1. Environment Configuration (.ct)

The `.ct` file uses a simple `KEY=value` format. Lines starting with `#` are comments.

```ini
# Application
APP_NAME="MyApp"
APP_ENV=local
APP_DEBUG=true
APP_URL="http://localhost"
APP_TIMEZONE="Asia/Manila"
APP_SESSION_TIMEOUT=1800
APP_SESSION_WARNING=120
```

### Application Keys

| Key | Description |
|---|---|
| `APP_NAME` | Application display name. |
| `APP_ENV` | Environment: `local`, `development`, `staging`, `production`. |
| `APP_DEBUG` | Enable debug mode: `true` / `false`. |
| `APP_URL` | Base URL of the application (e.g., `http://localhost`). |
| `APP_TIMEZONE` | PHP timezone string (e.g., `Asia/Manila`). |
| `APP_SESSION_TIMEOUT` | Session idle timeout in seconds before forced logout. |
| `APP_SESSION_WARNING` | Seconds before showing a session expiry warning. |

## 2. PHP Configuration (.ct.php)

For dynamic configuration that requires PHP evaluation, the framework supports `.ct.php` files. These files return an associative array that gets merged into the configuration.

```php
<?php
return [
    'APP_DEBUG' => true,
    'APP_URL' => 'http://' . $_SERVER['HTTP_HOST'],
];
```

## 3. Database Configuration

```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=myapp_db
DB_USERNAME=root
DB_PASSWORD=secret
DB_CHARSET=utf8mb4
```

| Key | Description |
|---|---|
| `DB_CONNECTION` | Database driver: `mysql`, `sqlite`, etc. |
| `DB_HOST` | Database server hostname. |
| `DB_PORT` | Database server port. |
| `DB_DATABASE` | Database name. |
| `DB_USERNAME` | Database username. |
| `DB_PASSWORD` | Database password. |
| `DB_CHARSET` | Character set (default: `utf8mb4`). |

### Using `connect_db()`

The framework provides a `connect_db()` helper that reads these constants and returns a PDO connection:

```php
$pdo = connect_db();
```

## 4. Redis Configuration

```ini
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DB=0
```

| Key | Description |
|---|---|
| `REDIS_HOST` | Redis server hostname. |
| `REDIS_PORT` | Redis server port. |
| `REDIS_PASSWORD` | Redis password (leave empty if none). |
| `REDIS_DB` | Redis database index (0–15). |

Redis is used by `CacheService` and `RedisService`.

## 5. Mail Configuration

```ini
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@myapp.com"
MAIL_FROM_NAME="MyApp"
```

| Key | Description |
|---|---|
| `MAIL_DRIVER` | Mail transport: `smtp`, `mail`, `sendmail`. |
| `MAIL_HOST` | SMTP server hostname. |
| `MAIL_PORT` | SMTP server port. |
| `MAIL_USERNAME` | SMTP username. |
| `MAIL_PASSWORD` | SMTP password. |
| `MAIL_ENCRYPTION` | Encryption: `tls`, `ssl`, or empty. |
| `MAIL_FROM_ADDRESS` | Default "From" email address. |
| `MAIL_FROM_NAME` | Default "From" display name. |

## 6. Directory Constants

The framework maps directory paths from `.ct` to PHP constants:

```ini
DIR_SITE="/var/www/html"
DIR_STORAGE="storage"
DIR_CONFIG="brain/config"
DIR_CONTROLLERS="brain/controllers"
DIR_MODELS="brain/models"
DIR_SERVICES="brain/classes/Services"
DIR_UTILS="brain/classes/Utils"
DIR_ROUTES="brain/routes"

DIR_CSS="assets/css"
DIR_FONTS="assets/fonts"
DIR_IMAGES="assets/images"
DIR_JS="assets/js"

DIR_LAYOUT="brain/layout"
DIR_AUTH="brain/layout/auth"
DIR_PORTALS="brain/portals"
DIR_PAGES="brain/pages"
```

These become:

| Constant | Resolved Path |
|---|---|
| `APP_ROOT` | `DIR_SITE` |
| `ROOT_DIR` | `DIR_SITE` |
| `APP_VIEWS` | `DIR_LAYOUT` |
| `APP_STORAGE` | `DIR_STORAGE` |
| `APP_CONFIG` | `DIR_CONFIG` |
| `APP_CONTROLLERS` | `DIR_CONTROLLERS` |
| `APP_MODELS` | `DIR_MODELS` |
| `APP_SERVICES` | `DIR_SERVICES` |
| `APP_UTILS` | `DIR_UTILS` |
| `APP_ROUTES` | `DIR_ROUTES` |
| `APP_DATABASE` | `DIR_STORAGE/database` |
| `APP_LOGS` | `DIR_STORAGE/logs` |
| `APP_CSS` | `DIR_CSS` |
| `APP_FONTS` | `DIR_FONTS` |
| `APP_IMAGES` | `DIR_IMAGES` |
| `APP_JS` | `DIR_JS` |
| `APP_VENDOR` | `DIR_SITE/vendor` |
| `APP_LAY` | `DIR_LAYOUT` |
| `APP_AUTH` | `DIR_AUTH` |
| `APP_PORTALS` | `DIR_PORTALS` |

## 7. URL Constants

```ini
URL_PORTAL="http://localhost/portal"
URL_ADMIN="http://localhost/admin"
URL_API="http://localhost/api"
```

These become:

| Constant | Description |
|---|---|
| `APP_ROOT_URL` | Base URL from `APP_URL`. |
| `APP_HOST_ROOT` | Host portion of `APP_URL`. |
| `APP_*_URL` | URL variant for each directory constant. |
| `APP_PORTALS_URL` | `URL_PORTAL` |
| `APP_ADMIN_URL` | `URL_ADMIN` |
| `APP_API_URL` | `URL_API` |

### File Include Constants

| Constant | Description |
|---|---|
| `APP_SCRIPTS_FILE` | Path to the shared scripts include file. |
| `APP_HEAD_FILE` | Path to the HTML head partial. |
| `APP_HEADER_FILE` | Path to the site header partial. |
| `APP_FOOTER_FILE` | Path to the site footer partial. |

## 8. Helper Functions

### `app_log($message, $context, $level, $category)`

Shorthand for `LoggerService::getInstance()->log(...)`. Logs a message to the default channel.

```php
app_log('Order placed', ['order_id' => 123], 'info', 'billing');
```

### `connect_db()`

Creates and returns a PDO connection using the configured database constants (`DB_HOST`, `DB_DATABASE`, etc.). Throws an exception on failure.

```php
$pdo = connect_db();
$result = $pdo->query('SELECT * FROM users');
```

### Reading config values in PHP

After the `.ct` file is parsed, all keys are available as PHP constants:

```php
if (APP_DEBUG) {
    error_reporting(E_ALL);
}

$timezone = APP_TIMEZONE;
```
