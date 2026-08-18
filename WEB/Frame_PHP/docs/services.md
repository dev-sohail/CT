# Services

The Frame PHP framework uses a service-oriented architecture. Services are reusable, single-responsibility classes that encapsulate application logic (auth, caching, logging, permissions, etc.). They live in `brain/classes/` under the `Services\` namespace.

## 1. Service Architecture

Services follow these principles:

- **Singleton-like access** — Services are instantiated once and retrieved via their static `getInstance()` method.
- **Constructor injection** — Dependencies are passed through the constructor (e.g., `PermissionService` accepts `$defaultPermissions`).
- **Namespace convention** — All built-in services are in `Services\` (e.g., `Services\AuthService`).
- **Location** — Service files live in `brain/classes/Services/`.

### Accessing a Service

```php
use Services\AuthService;

$auth = AuthService::getInstance();
$auth->login($username, $password);
```

## 2. AuthService API

`AuthService` handles user authentication, session management, and password resets.

### `register($username, $password, $email)`
Register a new user. Hashes the password and stores the user.

### `login($username, $password)`
Authenticate a user and start a session. Returns `true` on success.

### `logout()`
Destroy the current session and log the user out.

### `attemptRememberMe()`
Check for a valid "remember me" token and restore the session.

### `enforceSessionTimeout()`
Enforce the configured `APP_SESSION_TIMEOUT`. Redirects to login if the session has expired.

### `usernameExists($username)`
Check if a username already exists. Returns `true`/`false`.

### `emailExists($email)`
Check if an email already exists. Returns `true`/`false`.

### `hashPassword($password)`
Return a bcrypt hash of the given password.

### `verifyPassword($password, $hash)`
Verify a plaintext password against a stored hash. Returns `true`/`false`.

### `createResetToken($email)`
Generate a password reset token for the given email address.

### `verifyResetToken($token)`
Validate a password reset token. Returns `true`/`false`.

### `completeReset($token, $newPassword)`
Apply a password reset using a valid token.

## 3. CSRF Protection

`CsrfService` provides cross-site request forgery protection for all forms.

### `generateToken()`
Create a new CSRF token and store it in the session.

### `validateToken($token)`
Validate a submitted token against the session token. Returns `true`/`false`.

### `regenerateToken()`
Replace the current session token with a new one (use after form submission).

### `getTokenName()`
Return the form field name for the CSRF token (default: `_csrf_token`).

### Usage in forms

```php
use Services\CsrfService;

$csrf = CsrfService::getInstance();
?>
<form method="POST">
    <input type="hidden" name="<?= $csrf->getTokenName() ?>" value="<?= $csrf->generateToken() ?>">
    <!-- ... -->
</form>
```

### Validation in controllers

```php
$token = $_POST[CsrfService::getInstance()->getTokenName()];
if (!CsrfService::getInstance()->validateToken($token)) {
    // reject request
}
```

## 4. Caching

The framework provides two layers: a high-level `CacheService` and a low-level `RedisService`.

### CacheService

Redis-backed cache with a simple key/value API.

| Method | Description |
|---|---|
| `get($key, $default)` | Retrieve a cached value. Returns `$default` if missing. |
| `put($key, $value, $ttl)` | Store a value with an optional TTL in seconds. |
| `forget($key)` | Remove a single key from the cache. |
| `flush()` | Clear the entire cache. |

```php
use Services\CacheService;

$cache = CacheService::getInstance();

$cache->put('user.42', $userData, 3600); // 1 hour TTL
$user = $cache->get('user.42', null);
$cache->forget('user.42');
```

### RedisService

Low-level Redis wrapper using [Predis](https://github.com/predis/predis). Use this when you need direct Redis commands beyond simple get/put/forget.

```php
use Services\RedisService;

$redis = RedisService::getInstance();
$redis->set('key', 'value');
$redis->expire('key', 300);
```

## 5. Logging (Monolog via LoggerService)

`LoggerService` wraps [Monolog](https://github.com/Seldaek/monolog) and provides channel-based logging.

### `init($channel)`
Initialize a logging channel (creates a new Monolog instance for that channel).

### `get($channel)`
Retrieve an existing logger channel.

### `log($message, $context, $level, $category)`
Write a log entry.

- `$message` — The log message (string).
- `$context` — Additional data (array).
- `$level` — Log level: `debug`, `info`, `warning`, `error`, `critical`.
- `$category` — Optional category string for filtering.

```php
use Services\LoggerService;

$logger = LoggerService::getInstance();
$logger->init('app');

$logger->log('User logged in', ['user_id' => 42], 'info', 'auth');
$logger->log('Payment failed', ['order_id' => 1001], 'error', 'billing');
```

## 6. Permissions

`PermissionService` provides role-based access control.

### Constructor

```php
new PermissionService($defaultPermissions)
```

- `$defaultPermissions` — An associative array mapping roles to arrays of permissions.

### `can($role, $permission)`

Check if a role has a specific permission. Returns `true`/`false`.

```php
use Services\PermissionService;

$perms = new PermissionService([
    'admin'  => ['create', 'read', 'update', 'delete'],
    'editor' => ['create', 'read', 'update'],
    'viewer' => ['read'],
]);

if ($perms->can($user->role, 'delete')) {
    // allow delete action
}
```

## 7. Creating Custom Services

To add a custom service:

1. Create a new file in `brain/classes/Services/` (e.g., `PaymentService.php`).
2. Use the `Services\` namespace.
3. Implement `getInstance()` for singleton access.

```php
namespace Services;

class PaymentService
{
    private static ?self $instance = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function charge(float $amount, string $method): bool
    {
        // payment logic here
    }
}
```

4. Use it anywhere:

```php
use Services\PaymentService;

PaymentService::getInstance()->charge(29.99, 'card');
```

## 8. Using Services in Controllers

Services are accessed directly from controller methods. There is no dependency injection container — use static `getInstance()` calls.

```php
use Services\AuthService;
use Services\CsrfService;
use Services\CacheService;

class LoginController extends Controller
{
    public function show()
    {
        $csrf = CsrfService::getInstance();
        require APP_AUTH . '/login.php';
    }

    public function store()
    {
        $csrfToken = $_POST[CsrfService::getInstance()->getTokenName()];
        if (!CsrfService::getInstance()->validateToken($csrfToken)) {
            die('Invalid CSRF token');
        }

        $auth = AuthService::getInstance();
        if ($auth->login($_POST['username'], $_POST['password'])) {
            CacheService::getInstance()->put('last_login.' . $_SESSION['user_id'], time(), 86400);
            header('Location: /dashboard');
        } else {
            header('Location: /login?error=1');
        }
    }
}
```
