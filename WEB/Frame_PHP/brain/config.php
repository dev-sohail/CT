<?php
// 1. System Constants & Environment
// --------------------------------------------------
define('APP_ROOT', dirname(__DIR__));
define('ROOT_DIR', __DIR__ . '/');

// Module Autoloader — resolves Controllers\{Module}\* and Models\{Module}\*
// to backend/{module}/controllers/ and backend/{module}/models/
if (file_exists(__DIR__ . '/ModuleAutoloader.php')) {
    require_once __DIR__ . '/ModuleAutoloader.php';
}

// Load Composer vendor autoload
$vendorLoaded = false;
$vendorPath = APP_ROOT . '/storage/vendor/autoload.php';
if (is_file($vendorPath)) {
    require $vendorPath;
    $vendorLoaded = true;
}

// 2. Environment & Error Reporting
// --------------------------------------------------
$envFile = APP_ROOT . '/.frame.php';
$env = [];

if (file_exists($envFile)) {
    $env = include $envFile;
} elseif (file_exists(APP_ROOT . '/.frame')) {
    $lines = file(APP_ROOT . '/.frame', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $env[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
    }
}

foreach ($env as $k => $v) { if (getenv($k) === false) { @putenv($k . '=' . (string)$v); } }

date_default_timezone_set($env['APP_TIMEZONE'] ?? 'UTC');

$app_debug = filter_var($env['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
$app_env = $env['APP_ENV'] ?? 'production';

if ($app_debug || $app_env === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

if (version_compare(phpversion(), '8.0.0', '<') == TRUE) {
    exit('Application requires PHP 8.0+');
}

if (function_exists('ob_gzhandler') && !headers_sent()) {
    @ob_start('ob_gzhandler');
}

// 3. Database Connection
// --------------------------------------------------
if (!function_exists('connect_db')) {
    class OfflinePDO {
        public function query($sql) { throw new PDOException('DB offline'); }
        public function prepare($sql) { throw new PDOException('DB offline'); }
        public function exec($sql) { throw new PDOException('DB offline'); }
        public function beginTransaction() { throw new PDOException('DB offline'); }
        public function commit() { throw new PDOException('DB offline'); }
        public function rollBack() { throw new PDOException('DB offline'); }
    }
    function connect_db($prefix = 'DB_') {
        global $env;
        $driver = getenv($prefix . 'CONNECTION') ?? ($env[$prefix . 'CONNECTION'] ?? 'mysql');
        $servername = getenv($prefix . 'HOST') ?? ($env[$prefix . 'HOST'] ?? "localhost");
        $username = getenv($prefix . 'USERNAME') ?? ($env[$prefix . 'USERNAME'] ?? "root");
        $password = getenv($prefix . 'PASSWORD') ?? ($env[$prefix . 'PASSWORD'] ?? "");
        $dbname = getenv($prefix . 'DATABASE') ?? ($env[$prefix . 'DATABASE'] ?? "frame_db");
        $dbport = getenv($prefix . 'PORT') ?? ($env[$prefix . 'PORT'] ?? ($driver === 'pgsql' ? 5432 : 3306));
        $charset = getenv($prefix . 'CHARSET') ?? ($env[$prefix . 'CHARSET'] ?? "utf8mb4");
        $dsn = ($driver === 'pgsql')
            ? "pgsql:host=$servername;port=$dbport;dbname=$dbname"
            : "mysql:host=$servername;port=$dbport;dbname=$dbname;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            return new \Services\PgCompatPDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            return new OfflinePDO();
        }
    }
}

$pdo = connect_db('DB_');
if (php_sapi_name() === 'cli' || getenv('RUN_MIGRATIONS') === '1') {
    try {
        $migrationService = new \Services\MigrationService($pdo);
        $migrationService->applyPending();
    } catch (\Throwable $e) {
        error_log('Migration error on boot: ' . $e->getMessage());
    }
}
// Harden the session cookie: HttpOnly (not readable by JS), SameSite=Lax
// (mitigates CSRF), and Secure only when served over HTTPS so local/HTTP
// development still works. Must be set BEFORE session_start().
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

define('APP_SESSION_TIMEOUT', (int)($env['APP_SESSION_TIMEOUT'] ?? 1800));
define('APP_SESSION_WARNING', (int)($env['APP_SESSION_WARNING'] ?? 120));

// 4. URL & Path Configuration
// --------------------------------------------------
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$dir = str_replace('\\', '/', $scriptName);
$bp = dirname($dir);
$bp = str_replace('\\', '/', $bp);
if ($bp === '/' || $bp === '\\' || $bp === '.' || $bp === '') { $bp = ''; }
else { $bp = '/' . trim($bp, '/'); }
if (substr($bp, -7) === '/public') { $bp = rtrim(dirname($bp), '/'); if ($bp === '/' || $bp === '\\' || $bp === '.' || $bp === '') { $bp = ''; } }
// Prefer an explicit APP_URL from .frame.php or the environment (Docker sets it).
$appUrl = (!empty($env['APP_URL'])) ? $env['APP_URL'] : (getenv('APP_URL') ?: '');
if (!empty($appUrl)) {
    $p = parse_url($appUrl, PHP_URL_PATH);
    if (is_string($p)) {
        $p = rtrim($p, '/');
        if (substr($p, -7) === '/public') { $p = rtrim(dirname($p), '/'); }
        $bp = $p;
    }
}
define('APP_ROOT_URL', $bp);
$serverHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('APP_HOST_ROOT', $serverHost . APP_ROOT_URL);

// Directory constants from .frame
$dir_site      = $env['DIR_SITE']      ?? 'frontend';
$dir_storage   = $env['DIR_STORAGE']   ?? 'storage';
$dir_config    = $env['DIR_CONFIG']    ?? 'brain';
$dir_layout    = $env['DIR_LAYOUT']    ?? 'layout';
$dir_auth      = $env['DIR_AUTH']      ?? 'auth';
$dir_portals   = $env['DIR_PORTALS']   ?? 'portals';
$dir_pages     = $env['DIR_PAGES']     ?? 'pages';
$dir_css       = $env['DIR_CSS']       ?? 'css';
$dir_fonts     = $env['DIR_FONTS']     ?? 'fonts';
$dir_images    = $env['DIR_IMAGES']    ?? 'images';
$dir_js        = $env['DIR_JS']        ?? 'js';
$dir_routes    = $env['DIR_ROUTES']    ?? 'brain/routes';

// Module autoload root — all Controllers\X\* and Models\X\* resolve under this
$dir_controllers = 'backend';
$dir_models      = 'backend';
$dir_services    = $env['DIR_SERVICES'] ?? 'brain/classes';
$dir_utils       = $env['DIR_UTILS']    ?? 'brain/utils';

// Absolute path constants
define('APP_VIEWS',       APP_ROOT . '/' . $dir_site);
define('APP_STORAGE',     APP_ROOT . '/' . $dir_storage);
define('APP_CONFIG',       APP_ROOT . '/' . $dir_config);
define('APP_CONTROLLERS',  APP_ROOT . '/' . $dir_controllers);
define('APP_MODELS',       APP_ROOT . '/' . $dir_models);
define('APP_SERVICES',     APP_ROOT . '/' . $dir_services);
define('APP_UTILS',        APP_ROOT . '/' . $dir_utils);
define('APP_ROUTES',       APP_ROOT . '/' . $dir_routes);
define('APP_DATABASE',     APP_STORAGE . '/database');
define('APP_LOGS',         APP_STORAGE . '/logs');

define('APP_CSS',       APP_STORAGE . '/' . $dir_css);
define('APP_FONTS',     APP_STORAGE . '/' . $dir_fonts);
define('APP_IMAGES',    APP_STORAGE . '/' . $dir_images);
define('APP_JS',        APP_STORAGE . '/' . $dir_js);
define('APP_VENDOR',    APP_STORAGE . '/vendor');

define('APP_LAY',       APP_VIEWS . '/' . $dir_layout);
define('APP_AUTH',      APP_VIEWS . '/' . $dir_auth);
define('APP_PORTALS',   APP_VIEWS . '/' . $dir_portals);

define('APP_STORAGE_URL',  APP_ROOT_URL . '/' . $dir_storage);
define('APP_VIEWS_URL',    APP_ROOT_URL . '/' . $dir_site);
define('APP_AUTH_URL',     APP_VIEWS_URL . '/' . $dir_auth);
define('APP_LAY_URL',      APP_VIEWS_URL . '/' . $dir_layout);
define('APP_PAGES_URL',    APP_VIEWS_URL . '/' . $dir_pages);
define('APP_CCSS_URL',     APP_STORAGE_URL . '/' . $dir_css);
define('APP_CJS_URL',      APP_STORAGE_URL . '/' . $dir_js);
define('APP_IMAGES_URL',   APP_STORAGE_URL . '/' . $dir_images);
define('APP_VENDOR_URL',   APP_STORAGE_URL . '/vendor');

// 5. Logging (Monolog-backed, backward-compatible with old app_log())
// --------------------------------------------------
if (!function_exists('app_log')) {
    function app_log($message, array $context = [], string $level = 'INFO', ?string $category = null, ?string $fileBase = null): void {
        if (!\class_exists(\Services\LoggerService::class)) {
            return;
        }
        if ($fileBase !== null) {
            static $channels = [];
            $channel = $fileBase;
            if (!isset($channels[$channel])) {
                \Services\LoggerService::init($channel);
                $channels[$channel] = true;
            }
        }
        \Services\LoggerService::log((string)$message, $context, $level, $category);
    }
}

// 6. File Constants
// --------------------------------------------------
define('APP_SCRIPTS_FILE',     APP_LAY . '/scripts.ct.php');
define('APP_HEAD_FILE',        APP_LAY . '/head.ct.php');
define('APP_HEADER_FILE',      APP_LAY . '/header.ct.php');
define('APP_FOOTER_FILE',      APP_LAY . '/footer.ct.php');
define('APP_CCSS_FILE',        APP_CSS . '/custom-styles.css');
define('APP_CONFIG_FILE',      APP_CONFIG . '/config.php');

define('APP_SCRIPTS_URL',      APP_LAY_URL . '/scripts.ct.php');
define('APP_HEAD_URL',         APP_LAY_URL . '/head.ct.php');
define('APP_HEADER_URL',       APP_LAY_URL . '/header.ct.php');
define('APP_FOOTER_URL',       APP_LAY_URL . '/footer.ct.php');

define('APP_ADMIN_MENU',           APP_LAY . '/FloatNav.ct.php');
define('APP_ADMIN_VIEWS',          APP_VIEWS . '/admin');
define('APP_PORTAL_MENU',  APP_LAY . '/FloatNav.ct.php');
define('APP_PORTAL_INDEX', APP_PORTALS . '/index.ct.php');

// URL segments
$url_portal = $env['URL_PORTAL'] ?? 'portal';
$url_admin  = $env['URL_ADMIN']  ?? 'admin';
$url_api    = $env['URL_API']    ?? 'api';

define('APP_PORTALS_URL', APP_ROOT_URL . '/' . $url_portal);
define('APP_ADMIN_URL',   APP_ROOT_URL . '/' . $url_admin);
define('APP_API_URL',     APP_ROOT_URL . '/' . $url_api);

$currentPage = basename($_SERVER['PHP_SELF']);

// 7. Routing System
// --------------------------------------------------
require_once __DIR__ . '/router.php';
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use function FastRoute\cachedDispatcher;

// Module-aware autoloaders for Controllers and Models
// Resolution order: backend/ (base classes) → backend/{module}/[controllers|models]/ → brain/
spl_autoload_register(function ($class) {
    $prefix = 'Controllers\\';
    if (strncmp($prefix, $class, 12) !== 0) return;
    $relative = substr($class, 12);
    $safe = str_replace('\\', '/', $relative);

    // base: backend/Controllers/X.php (e.g. BaseController)
    $baseFile = APP_CONTROLLERS . '/' . $safe . '.php';
    if (file_exists($baseFile)) { require $baseFile; return; }

    // module: backend/{module}/controllers/{Sub/...}.php
    $parts = explode('\\', $relative);
    if (count($parts) >= 2) {
        $modFile = APP_CONTROLLERS . '/' . strtolower($parts[0]) . '/controllers/' . implode('/', array_slice($parts, 1)) . '.php';
        if (file_exists($modFile)) { require $modFile; return; }
    }
}, true, true);

spl_autoload_register(function ($class) {
    $prefix = 'Models\\';
    if (strncmp($prefix, $class, 6) !== 0) return;
    $relative = substr($class, 6);
    $safe = str_replace('\\', '/', $relative);

    // base: backend/Models/X.php (e.g. BaseModel)
    $baseFile = APP_MODELS . '/' . $safe . '.php';
    if (file_exists($baseFile)) { require $baseFile; return; }

    // module: backend/{module}/models/{Sub/...}.php
    $parts = explode('\\', $relative);
    if (count($parts) >= 2) {
        $modFile = APP_MODELS . '/' . strtolower($parts[0]) . '/models/' . implode('/', array_slice($parts, 1)) . '.php';
        if (file_exists($modFile)) { require $modFile; return; }
    }
}, true, true);

spl_autoload_register(function ($class) {
    $prefix = 'Services\\';
    if (strncmp($prefix, $class, 9) !== 0) return;
    $relative = substr($class, 9);
    $file = APP_SERVICES . '/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) { require $file; return; }
    $alt = APP_ROOT . '/brain/services/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($alt)) { require $alt; return; }
    $alt2 = APP_ROOT . '/brain/classes/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($alt2)) { require $alt2; return; }
});

// UI component library autoloader — resolves UI\ and UI\Components\ to frontend/layout/ui/
spl_autoload_register(function ($class) {
    $prefix = 'UI\\';
    if (strncmp($prefix, $class, 3) !== 0) return;
    $relative = substr($class, 3);
    $file = APP_LAY . 'ui/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) { require $file; return; }
});

global $app_routes_list;
$app_routes_list = [];

if (!function_exists('add_app_route')) {
    function add_app_route($r, $method, $uri, $handler) {
        global $app_routes_list;
        $r->addRoute($method, $uri, $handler);
        $app_routes_list[$uri] = ['method' => $method, 'handler' => $handler];
    }
}

if (!function_exists('get_routing_table')) {
    function get_routing_table() {
        global $app_routes_list;
        echo "<pre>";
        print_r($app_routes_list);
        echo "</pre>";
    }
}

// Route dispatcher
$routesCacheDir = APP_STORAGE . '/cache';
if (!is_dir($routesCacheDir)) { @mkdir($routesCacheDir, 0777, true); }
$routesCacheFile = $routesCacheDir . '/fast_route.cache.php';
$dispatcher = cachedDispatcher(
    function(RouteCollector $r) use ($url_portal, $url_admin, $url_api) {
        require APP_ROUTES . '/web.php';
    },
    [
        'cacheFile' => $routesCacheFile,
        'cacheDisabled' => ($app_debug === true)
    ]
);

if (class_exists(\Services\AuthService::class) && php_sapi_name() !== 'cli') {
    $auth = new \Services\AuthService($pdo);
    if (empty($_SESSION['logged_in'])) {
        if (!empty($_COOKIE['remember_me'])) {
            $auth->attemptRememberMe();
        }
    } else {
        $auth->enforceSessionTimeout();
    }
}

// HTTP dispatch
if (php_sapi_name() !== 'cli') {
    $httpMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    if (false !== $pos = strpos($uri, '?')) { $uri = substr($uri, 0, $pos); }
    $uri = rawurldecode($uri);
    $base = APP_ROOT_URL;
    if (strpos($uri, $base) === 0) { $uri = substr($uri, strlen($base)); }
    if (empty($uri) || $uri[0] !== '/') $uri = '/' . $uri;
    if ($uri !== '/' && substr($uri, -1) === '/') $uri = rtrim($uri, '/');

    $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
    switch ($routeInfo[0]) {
        case FastRoute\Dispatcher::NOT_FOUND:
            http_response_code(404);
            $file404 = APP_VIEWS . '/public/404.ct.php';
            if (file_exists($file404)) { include $file404; } else { echo '404 Not Found'; }
            exit;
        case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
            http_response_code(405);
            echo "405 Method Not Allowed";
            exit;
        case FastRoute\Dispatcher::FOUND:
            $handler = $routeInfo[1];
            $vars = $routeInfo[2];
            if ($handler instanceof Closure) {
                echo $handler($vars);
                exit;
            }
            if (is_string($handler) && strpos($handler, '@') !== false) {
                if (\class_exists(\Services\QueryLogger::class)) {
                    \Services\QueryLogger::enable();
                }
                list($controllerClass, $method) = explode('@', $handler);
                if (strpos($controllerClass, 'Controllers\\') !== 0) {
                    $controllerClass = 'Controllers\\' . $controllerClass;
                }
                if (class_exists($controllerClass)) {
                    $controller = new $controllerClass();
                    if (method_exists($controller, $method)) {
                        echo call_user_func_array([$controller, $method], $vars);
                        exit;
                    }
                    die("Method $method not found in controller $controllerClass");
                }
            }
            extract($vars);
            if (is_string($handler) && file_exists($handler)) {
                include $handler;
                exit;
            }
            http_response_code(500);
            echo "Handler not found: " . (is_string($handler) ? $handler : 'unknown');
            exit;
    }
}
