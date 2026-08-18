# Frame PHP Framework

A modular PHP framework with FastRoute-based routing, module autoloading, and a built-in CLI tool. Built for rapid development with Tailwind CSS, Monolog logging, Redis caching, and an Active Record ORM.

## Requirements

- PHP 8.0+
- MySQL or PostgreSQL
- Redis (optional, for caching)
- Node.js & npm (for frontend assets)

## Quick Start

```bash
# 1. Clone the repository
git clone <repo-url> && cd Frame_PHP

# 2. Install PHP dependencies
composer install

# 3. Install frontend dependencies
npm install

# 4. Configure environment
cp .ct .ct.local
# Edit .ct with your database credentials

# 5. Run migrations
php cli.php migrate

# 6. Build frontend assets
npm run build

# 7. Serve the application
php -S localhost:8084 -t public
```

## Directory Structure

```
Frame_PHP/
├── backend/                    # Application code (Controllers, Models)
│   ├── BaseController.php      # Base controller (view, CSRF, permissions)
│   ├── BaseModel.php           # Base model (query helpers)
│   ├── Model.php               # Active Record model
│   ├── admin/                  # Admin module
│   ├── auth/                   # Auth module
│   ├── api/                    # API module
│   └── portal/                 # Portal module
├── brain/                      # Framework core
│   ├── config.php              # Configuration & bootstrap
│   ├── router.php              # FastRoute internals
│   ├── ModuleAutoloader.php    # Module namespace autoloader
│   ├── routes/
│   │   └── web.php             # Route definitions
│   └── classes/                # Service classes (PSR-4: Services\)
│       ├── AuthService.php
│       ├── CacheService.php
│       ├── CsrfService.php
│       ├── LoggerService.php
│       ├── MigrationService.php
│       ├── PermissionService.php
│       ├── RedisService.php
│       └── ...
├── frontend/                   # View templates
│   ├── layout/                 # Shared layouts (head, header, footer, scripts)
│   ├── admin/                  # Admin views
│   ├── auth/                   # Auth views (login)
│   └── public/                 # Public views (404)
├── public/                     # Web root
│   └── index.php               # Entry point
├── storage/                    # Generated & runtime files
│   ├── cache/                  # Route cache
│   ├── css/                    # Compiled CSS
│   ├── js/                     # Compiled JS
│   ├── database/migrations/    # Migration files
│   ├── logs/                   # Application logs
│   ├── resources-src/          # Source assets (Tailwind CSS, JS)
│   └── vendor/                 # Composer vendor directory
├── tests/                      # PHPUnit tests
├── .ct                         # Environment configuration
├── cli.php                     # CLI tool entry point
├── composer.json
├── package.json
├── phpunit.xml
├── tailwind.config.js
└── postcss.config.js
```

## Configuration

The `.ct` file at the project root defines all environment variables:

```ini
APP_NAME=Frame PHP Framework
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8084
APP_TIMEZONE=UTC

DB_CONNECTION=pgsql          # mysql or pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=frame_db
DB_USERNAME=root
DB_PASSWORD=root
DB_CHARSET=utf8mb4

REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DB=0

MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME=Frame PHP Framework

DIR_SITE=frontend
DIR_STORAGE=storage
DIR_CONFIG=brain
DIR_CONTROLLERS=backend
DIR_MODELS=backend
DIR_SERVICES=brain/services
DIR_ROUTES=brain/routes
```

## Routing

Routes are defined in `brain/routes/web.php`. Use the `add_app_route()` helper:

```php
<?php
use FastRoute\RouteCollector;

/** @var RouteCollector $r */

// Single method
add_app_route($r, 'GET', '/', 'Public\HomeController@index');

// Multiple methods
add_app_route($r, ['GET', 'POST'], '/login', 'Auth\AuthController@login');

// With route parameters
add_app_route($r, 'GET', '/users/{id}', 'Admin\UserController@show');
```

Route handlers use the `Controller@method` string format. The framework resolves the class under the `Controllers\` namespace automatically.

### Route Groups

```php
$r->addGroup('/admin', function (RouteCollector $r) {
    add_app_route($r, 'GET', '/dashboard', 'Admin\DashboardController@index');
    add_app_route($r, 'GET', '/users', 'Admin\UserController@index');
});
```

## Creating Modules

Use the CLI to scaffold a new module:

```bash
php cli.php make:module blog
```

This creates:

```
backend/blog/
├── controllers/
│   └── BlogController.php     # Controllers\Blog\BlogController
└── models/
    └── BlogModel.php          # Models\Blog\BlogModel
```

### Namespace Resolution

The module autoloader maps namespaces to directories:

| Namespace | Path |
|---|---|
| `Controllers\Admin\Dashboard` | `backend/admin/controllers/Dashboard.php` |
| `Controllers\Auth\Auth` | `backend/auth/controllers/Auth.php` |
| `Models\Admin\User` | `backend/admin/models/User.php` |
| `Services\CacheService` | `brain/classes/CacheService.php` |

### BaseController

All controllers extend `BaseController` which provides:

```php
class DashboardController extends BaseController
{
    public function index()
    {
        // Render a view
        $this->view('admin/dashboard', ['title' => 'Dashboard']);

        // Check permissions
        $this->requirePermission('admin.access');

        // CSRF protection (auto-applied on non-GET requests)
        $this->getCsrfToken();

        // Admin guard
        $this->requireAdmin();

        // Redirect
        $this->redirectWith('/admin/users', ['status' => 'created']);
    }
}
```

### Model (Active Record)

```php
use Models\Model;

class UserModel extends Model
{
    protected static ?string $table = 'users';
    protected static string $primaryKey = 'id';
}

// Usage
$user = UserModel::find(1);
$users = UserModel::where(['role' => 'admin']);
$all = UserModel::all();

$new = UserModel::create(['name' => 'John', 'email' => 'john@example.com']);
$new->update(['name' => 'Jane']);
$new->delete();

// Access attributes
$name = $user->get('name');
$user->set('name', 'Updated');
$array = $user->toArray();
```

## CLI Commands

```bash
php cli.php <command> [arguments]
```

| Command | Description |
|---|---|
| `migrate` | Run pending migrations |
| `migrate:fresh` | Drop all tables and re-run migrations |
| `make:module <name>` | Create a new module (controllers, models, views) |
| `make:controller <name>` | Create a new controller |
| `make:model <name>` | Create a new model |
| `make:migration <name>` | Create a new migration file |
| `cache:clear` | Clear application and Redis cache |
| `db:seed` | Seed database with default data |
| `user:create <u> <p> [role]` | Create a new user |
| `list:routes` | List all registered routes |
| `db:backup [file]` | Backup database to SQL file |
| `db:restore <file>` | Restore database from SQL file |
| `help` | Show help message |

## Frontend Build

The framework uses Tailwind CSS and esbuild for frontend assets.

```bash
# Build CSS and JS
npm run build

# Watch for changes (development)
npm run dev
```

### Source Files

- **CSS**: `storage/resources-src/css/input.css` → compiled to `storage/css/app.css`
- **JS**: `storage/resources-src/js/app.js` → bundled to `storage/js/app.js`

### Tailwind Configuration

Content paths are configured in `tailwind.config.js` to scan all `.ct.php` and `.php` files. Custom theming uses CSS custom properties for colors, fonts, borders, and shadows.

## Services

Service classes live in `brain/classes/` under the `Services\` namespace:

| Service | Purpose |
|---|---|
| `AuthService` | User authentication (delight-im/auth) |
| `CacheService` | File-based caching |
| `CsrfService` | CSRF token generation & validation |
| `LoggerService` | Monolog-backed logging |
| `MigrationService` | Database migration runner |
| `PasswordService` | Password hashing |
| `PermissionService` | Role-based access control |
| `QueryLogger` | SQL query logging |
| `RedisService` | Redis connection wrapper |

## Testing

```bash
# Run all tests
phpunit

# Or via npm
npm run test:php
```

Tests are located in the `tests/` directory. The bootstrap file loads the framework configuration before running tests.

## Logging

The framework uses Monolog via `LoggerService`. Use the global `app_log()` helper:

```php
app_log('User logged in', ['user_id' => $id], 'INFO', 'auth');
app_log('Payment failed', ['order_id' => $oid], 'ERROR', 'payments');
```

Logs are written to `storage/logs/`.

## License

MIT License. See [LICENSE](LICENSE) for details.
