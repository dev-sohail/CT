# Frame PHP — Architecture

## 1. Request Lifecycle

Every HTTP request follows this path:

```
public/.htaccess (rewrite rules)
  └─ public/index.php
       └─ brain/config.php (bootstrap)
            ├─ Load environment (.ct file)
            ├─ Register autoloaders (ModuleAutoloader, Composer, PSR-4)
            ├─ Connect database → global $pdo
            ├─ Start session (hardened: HttpOnly, SameSite=Lax)
            ├─ Run pending migrations (CLI only)
            ├─ Define path constants (APP_VIEWS, APP_STORAGE, etc.)
            ├─ Require brain/router.php (embedded FastRoute)
            ├─ Load brain/routes/web.php via cachedDispatcher()
            ├─ Authenticate via AuthService (remember-me, session timeout)
            └─ Dispatch HTTP (FastRoute\Dispatcher)
                 ├─ NOT_FOUND  → 404.ct.php
                 ├─ METHOD_NOT_ALLOWED → 405
                 └─ FOUND → resolve handler
                      ├─ Closure       → execute($vars)
                      ├─ String "X@y"  → Controllers\X → new $class → $method($vars)
                      └─ File path     → include $handler
```

The `.htaccess` in `public/` rewrites all non-file/non-directory requests to `index.php`. The `config.php` file is the true bootstrap — it runs everything before the route is dispatched.

## 2. Directory Structure

```
Frame_PHP/
├── public/
│   ├── index.php              # Entry point (includes brain/config.php)
│   └── .htaccess              # Apache rewrite → index.php
│
├── brain/                     # Core framework logic
│   ├── config.php             # Bootstrap: env, DB, session, constants, autoloaders, routing
│   ├── router.php             # Embedded FastRoute library (single-file, no vendor dependency)
│   ├── ModuleAutoloader.php   # Early autoloader for Controllers\* and Models\*
│   ├── routes/
│   │   └── web.php            # Route definitions using add_app_route()
│   ├── classes/               # Service classes (Services\* namespace)
│   │   ├── AuthService.php
│   │   ├── CsrfService.php
│   │   ├── PermissionService.php
│   │   ├── PasswordService.php
│   │   ├── CacheService.php
│   │   ├── RedisService.php
│   │   ├── LoggerService.php
│   │   ├── MigrationService.php
│   │   ├── QueryLogger.php
│   │   └── AuthLibraryService.php
│   ├── services/              # Additional services (Services\* namespace)
│   └── utils/                 # Utility functions
│
├── backend/                   # Controllers and Models
│   ├── BaseController.php     # Base controller (view(), CSRF, permissions)
│   ├── BaseModel.php          # Base model (queryAll, queryOne, execute, insertGetId)
│   ├── Model.php              # Active Record model (find, where, all, create, update, delete)
│   ├── admin/
│   │   ├── controllers/       # Admin\Controllers\* → Admin\*
│   │   └── models/            # Admin\Models\* → Admin\*
│   ├── auth/
│   │   ├── controllers/       # Auth\Controllers\* → Auth\*
│   │   └── models/            # Auth\Models\* → Auth\*
│   ├── api/
│   │   ├── controllers/       # Api\Controllers\* → Api\*
│   │   └── models/            # Api\Models\* → Api\*
│   ├── portal/
│   │   ├── controllers/       # Portal\Controllers\* → Portal\*
│   │   └── models/            # Portal\Models\* → Portal\*
│   └── shared/
│       ├── controllers/       # Shared controllers across modules
│       └── models/            # Shared models across modules
│
├── frontend/                  # View templates
│   ├── index.html
│   ├── layout/
│   │   ├── head.ct.php        # <html><head> opening
│   │   ├── header.ct.php      # Site header/nav
│   │   ├── footer.ct.php      # Site footer
│   │   ├── scripts.ct.php     # JS includes
│   │   └── FloatNav.ct.php    # Floating admin/portal navigation
│   ├── auth/
│   │   └── login.ct.php       # Login form view
│   ├── admin/                 # Admin view templates
│   ├── portal/                # Portal view templates
│   └── public/
│       └── 404.ct.php         # 404 error page
│
├── storage/                   # Compiled assets and runtime data
│   ├── vendor/                # Composer dependencies (vendor-dir: storage/vendor)
│   ├── cache/                 # FastRoute compiled cache (fast_route.cache.php)
│   ├── css/                   # Compiled CSS
│   ├── js/                    # Compiled JS
│   ├── images/
│   ├── fonts/
│   ├── webfonts/
│   ├── uploads/
│   ├── database/
│   │   └── migrations/        # Migration SQL files
│   ├── logs/                  # Application logs (Monolog)
│   └── resources-src/         # Source files (input.css, app.js)
│
├── tests/
│   └── bootstrap.php
│
├── .ct                        # Environment file (key=value pairs)
├── .htaccess                  # Root rewrite rules
├── composer.json
├── package.json
├── tailwind.config.js
├── postcss.config.js
└── phpunit.xml
```

## 3. Autoloading System

The framework uses four `spl_autoload_register` chains, loaded in `brain/config.php`. They execute in reverse registration order (last registered = first checked).

### 3.1 Composer Autoload (PSR-4)

Registered via `storage/vendor/autoload.php`. Handles third-party packages and the `Services\` namespace:

```json
"autoload": {
    "psr-4": {
        "Services\\": "brain/classes/"
    },
    "files": [
        "backend/Model.php",
        "brain/classes/QueryLogger.php"
    ]
}
```

### 3.2 ModuleAutoloader (early, in brain/ModuleAutoloader.php)

Registered first (loaded before Composer). Resolves `Controllers\{Module}\*` and `Models\{Module}\*` using the `backend/{module}/controllers/` and `backend/{module}/models/` convention. Checks two base directories (project root backend/ and parent fallback backend/) for symlink/docker compatibility.

### 3.3 Controllers\ Resolver (in brain/config.php)

```php
spl_autoload_register(function ($class) {
    $prefix = 'Controllers\\';
    // Resolution order:
    // 1. backend/Controllers/X.php  (base classes like BaseController)
    // 2. backend/{module}/controllers/Sub.php  (module controllers)
});
```

### 3.4 Models\ Resolver (in brain/config.php)

```php
spl_autoload_register(function ($class) {
    $prefix = 'Models\\';
    // Resolution order:
    // 1. backend/Models/X.php  (base classes like BaseModel)
    // 2. backend/{module}/models/Sub.php  (module models)
});
```

### 3.5 Services\ Resolver (in brain/config.php)

```php
spl_autoload_register(function ($class) {
    $prefix = 'Services\\';
    // Resolution order:
    // 1. brain/classes/X.php
    // 2. brain/services/X.php
});
```

### 3.6 UI\ Resolver (in brain/config.php)

```php
spl_autoload_register(function ($class) {
    $prefix = 'UI\\';
    // Resolves to: frontend/layout/ui/{name}.php
});
```

### Namespace → File Mapping Examples

| Namespace | File |
|---|---|
| `Controllers\BaseController` | `backend/BaseController.php` |
| `Controllers\Admin\DashboardController` | `backend/admin/controllers/DashboardController.php` |
| `Controllers\Auth\AuthController` | `backend/auth/controllers/AuthController.php` |
| `Models\BaseModel` | `backend/BaseModel.php` |
| `Models\Admin\UserModel` | `backend/admin/models/UserModel.php` |
| `Services\CsrfService` | `brain/classes/CsrfService.php` |
| `Services\LoggerService` | `brain/classes/LoggerService.php` |

## 4. Module Convention

Modules organize controllers and models under a domain directory:

```
backend/
├── admin/
│   ├── controllers/
│   │   ├── DashboardController.php   → namespace Controllers\Admin
│   │   └── LoginController.php       → namespace Controllers\Admin
│   └── models/
│       └── UserModel.php             → namespace Models\Admin
├── auth/
│   ├── controllers/
│   │   └── AuthController.php        → namespace Controllers\Auth
│   └── models/
│       └── AuthModel.php             → namespace Models\Auth
├── api/
│   ├── controllers/
│   │   └── ApiController.php         → namespace Controllers\Api
│   └── models/
│       └── ApiModel.php              → namespace Models\Api
└── portal/
    ├── controllers/
    │   └── PortalController.php      → namespace Controllers\Portal
    └── models/
        └── PortalModel.php           → namespace Models\Portal
```

**Rule**: The first namespace segment after `Controllers\` or `Models\` is lowercased to form the directory name. `Controllers\Admin\DashboardController` resolves to `backend/admin/controllers/DashboardController.php`.

### Route Handler Notation

Routes in `brain/routes/web.php` use the `Module\Class@method` string format:

```php
add_app_route($r, ['GET', 'POST'], '/admin', 'Admin\DashboardController@index');
```

The dispatcher prepends `Controllers\` automatically if absent, instantiates the class, and calls the method with route `$vars`.

## 5. Service Container

There is no formal DI container. Dependencies are accessed via:

- **`global $pdo`** — The PDO connection set in `config.php:97` and inherited by all controllers/models via `BaseController::__construct()` and `BaseModel::__construct($pdo)`.
- **Direct instantiation** — Controllers create service instances on demand:

```php
// BaseController::__construct()
$this->permissionService = new PermissionService($this->pdo);
```

- **Service classes** live in `brain/classes/` (or `brain/services/`) under the `Services\` namespace:

| Service | Purpose |
|---|---|
| `AuthService` | Registration, login, logout, session timeout, password reset |
| `CsrfService` | Token generation, validation, 1-hour lifetime |
| `PermissionService` | Role-based access control (permissions + role_permissions tables) |
| `PasswordService` | Password hashing helpers |
| `CacheService` | Application caching |
| `RedisService` | Redis/Predis wrapper |
| `LoggerService` | Monolog-backed structured logging |
| `MigrationService` | Automatic schema migration on boot |
| `QueryLogger` | SQL query timing and logging |
| `AuthLibraryService` | Wrapper around `delight-im/auth` library |

Services receive `$pdo` in their constructor and manage their own state. There is no singleton or service locator pattern — each controller action creates fresh instances.

## 6. View System

### Rendering

Controllers call `$this->view($path, $data)` from `BaseController`:

```php
protected function view($path, $data = []) {
    $data['pdo'] = $this->pdo;
    extract($data);                          // Variables become local scope
    $viewFileCt = APP_VIEWS . '/' . $path . '.ct.php';
    $viewFilePhp = APP_VIEWS . '/' . $path . '.php';
    // Tries .ct.php first, falls back to .php
    require $viewFileCt; // or $viewFilePhp
}
```

Views are plain PHP files with `.ct.php` extension. The `extract($data)` call makes all `$data` keys available as local variables inside the view.

### Layout Inclusion

Layout partials are included via `include` or `require` within views:

```php
// In a controller action
$this->view('auth/login', [
    'csrf_token' => $this->getCsrfToken(),
    'pageTitle' => 'Login',
]);
```

```php
// In a view template (frontend/auth/login.ct.php)
$error = $error ?? null;               // Fallback for undefined variables
$csrf_token = $csrf_token ?? '';
?>
<div class="login-form">
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <!-- ... -->
    </form>
</div>
```

### Layout Partials

| Constant | File |
|---|---|
| `APP_HEAD_FILE` | `frontend/layout/head.ct.php` |
| `APP_HEADER_FILE` | `frontend/layout/header.ct.php` |
| `APP_FOOTER_FILE` | `frontend/layout/footer.ct.php` |
| `APP_SCRIPTS_FILE` | `frontend/layout/scripts.ct.php` |

Layout constants (`APP_LAY`, `APP_AUTH_URL`, etc.) are defined in `config.php` and available in all views for asset URLs and path references.

## 7. Database Layer

### PDO Connection

`config.php` calls `connect_db('DB_')` which reads `DB_CONNECTION`, `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD`, `DB_DATABASE`, `DB_PORT`, `DB_CHARSET` from the `.ct` environment file. Supports MySQL and PostgreSQL via DSN switching. On connection failure, returns an `OfflinePDO` stub that throws on any query.

PDO options set at connection time:
- `ERRMODE_EXCEPTION` — All errors throw PDOException
- `FETCH_ASSOC` — Default fetch mode is associative array
- `EMULATE_PREPARES = false` — Uses native prepared statements

### BaseModel Helpers

`Models\BaseModel` (`backend/BaseModel.php`) provides low-level query methods:

| Method | Returns | Purpose |
|---|---|---|
| `queryAll($sql, $params)` | `array` | Fetch all rows (FETCH_ASSOC) |
| `queryOne($sql, $params)` | `?array` | Fetch single row, null if not found |
| `queryValue($sql, $params)` | `mixed` | Fetch single column value |
| `execute($sql, $params)` | `int` | Execute statement, return affected rows |
| `insertGetId($sql, $params)` | `string` | Execute insert, return lastInsertId |
| `ensureTableExists($table, $createSql)` | `void` | Create table if missing |
| `ensureColumn($table, $column, $def)` | `void` | Add column if missing |
| `ensureIndex($table, $name, $sql)` | `void` | Add index if missing |

All query methods integrate with `QueryLogger` for timing when enabled.

### Model Active Record

`Models\Model` (`backend/Model.php`) extends `BaseModel` with an Active Record pattern:

```php
abstract class Model extends BaseModel {
    protected static ?string $table = null;
    protected static string $primaryKey = 'id';
    protected array $attributes = [];
    protected bool $exists = false;
}
```

| Method | Type | Description |
|---|---|---|
| `Model::table('users')` | static | Set table name (chainable) |
| `Model::find($id)` | static | Find by primary key, return model or null |
| `Model::where([...])` | static | Filter by conditions, return array of models |
| `Model::all()` | static | Fetch all rows, return array of models |
| `Model::create([...])` | static | Insert row, return populated model |
| `$model->update([...])` | instance | Update row, merge attributes |
| `$model->delete()` | instance | Delete row, set exists=false |
| `$model->toArray()` | instance | Return raw attributes array |
| `$model->get($key)` | instance | Get attribute value |
| `$model->set($key, $val)` | instance | Set attribute value |

The `$pdo` for static methods comes from `$GLOBALS['pdo']`.

## 8. Security

### CSRF Protection

- `CsrfService` generates tokens via `random_bytes(32)` stored in `$_SESSION['csrf_token']` with a 1-hour lifetime.
- `BaseController::__construct()` automatically calls `$this->requireCsrf()` for all non-GET/HEAD/OPTIONS/TRACE requests (except `/api/*` routes).
- Tokens are read from `$_POST['csrf_token']`, `$_GET['csrf_token']`, or headers (`X-CSRF-Token`, `X-CSRF`, `X-XSRF-Token`).
- Validation uses `hash_equals()` for timing-safe comparison.

### Session Hardening

Session cookies are configured in `config.php` before `session_start()`:

| Setting | Value |
|---|---|
| `httponly` | `true` — Not accessible via JavaScript |
| `samesite` | `Lax` — Mitigates CSRF via cross-site requests |
| `secure` | Dynamic — `true` when HTTPS detected (X-Forwarded-Proto or SERVER_PORT 443) |
| `lifetime` | `0` — Session cookie (expires on browser close) |

Session timeout is enforced via `APP_SESSION_TIMEOUT` (default 1800s = 30min). `AuthService::enforceSessionTimeout()` compares `$_SESSION['last_activity']` against the timeout threshold.

### Password Security

- `AuthService::hashPassword()` uses `password_hash(PASSWORD_DEFAULT)` (bcrypt).
- Login throttling: 5-second minimum between attempts, tracked in `$_SESSION['login_throttle']`.
- Remember-me tokens handled by `delight-im/auth` library.

### Permission Checks

- `PermissionService` manages a `permissions` + `role_permissions` table pair.
- `BaseController::can($key)` checks the current user's role against the permission map.
- `BaseController::requirePermission($key)` returns 403 if denied.
- The `admin` role bypasses all permission checks (`can()` returns true for admin).
- `BaseController::requireAdmin()` gates admin-only actions by checking `$_SESSION['role'] === 'admin'`.

### Additional Hardening

- `.htaccess` sets `X-Content-Type-Options: nosniff`, `X-XSS-Protection: 1; mode=block`.
- `Options -Indexes` prevents directory listing.
- Gzip compression enabled via `mod_deflate` and `ob_gzhandler()`.
- Asset caching via `mod_expires` (1-month TTL for images, CSS, JS).
