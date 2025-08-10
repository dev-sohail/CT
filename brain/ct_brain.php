<?php

/**
 * CyberTirah Framework - Bootstrap + Env loader (fixed)
 *
 * - No Composer / PSR-4 autoloading used. This file scans and requires framework core
 *   and utility PHP files explicitly (custom framework loader).
 * - Defensive and clear error handling added.
 * - loadAllUtilityClasses now returns an array of loaded classes->paths.
 * - Safe checks before instantiating Registry.
 *
 * @version 1.4.1
 * @author CyberTirah Development Team (fixed)
 * @license MIT
 * @since PHP 8.0+
 */

class MakingEnv
{
    private array $config = [];

    public function __construct(string $envFile = ROOT . '/.env')
    {
        $this->phpversion();

        if (file_exists($envFile)) {
            $this->loadEnv($envFile);
            error_log(".env file Loaded Successfully");
        } else {
            // Do not expose full path in production, but user asked explicit error.
            exit('<center style="margin-top: 8rem; color: red;">' .
                ".env file not found at:" . '</br>' . $envFile . '</br>' . " Using default values." .
                '</center>');
        }
    }

    private function phpversion(): void
    {
        if (version_compare(PHP_VERSION, '8.0', '<')) {
            http_response_code(500);
            exit('<center style="margin-top: 8rem;">This application requires at least PHP 8.0 or higher. Current version: ' . PHP_VERSION . '</center>');
        }
    }

    private function loadEnv(string $file): void
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $rawLine) {
            $line = trim($rawLine);

            // skip comments and empty lines
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // ignore lines that do not contain '='
            if (strpos($line, '=') === false) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // strip surrounding quotes if present
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }

            $this->config[$key] = $value;
            // populate superglobals for compatibility
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }

    public function get(string $key, $default = null)
    {
        return $this->config[$key] ?? $_ENV[$key] ?? getenv($key) ?: $default;
    }
}

class Bootstrap
{
    private float $startTime;
    private bool $isInitialized = false;
    private array $paths = [];
    private array $dbConfig = [];
    private array $mailConfig = [];
    private array $loadedClasses = [];
    private array $coreFiles = [];
    private MakingEnv $config;

    private function __construct(MakingEnv $config)
    {
        $this->startTime = microtime(true);
        $this->config = $config;
        $this->initialize();
    }

    public static function boot(): self
    {
        if (!defined('ROOT')) {
            define('ROOT', dirname(__DIR__));
        }
        if (!defined('APP_START_TIME')) {
            define('APP_START_TIME', microtime(true));
        }

        // Load environment first
        $envconfig = new MakingEnv();
        $instance = new self($envconfig);

        try {
            $loadedCore = $instance->loadCoreFiles();
        } catch (RuntimeException $e) {
            error_log("Failed to load critical core files: " . $e->getMessage());
            exit('<center style="margin-top: 8rem; color: red;">' . "Critical framework files failed to load. Please check the logs." . '</center>');
        }

        // Load utility classes (no autoloader). We will require their files and keep a map.
        $loadedUtilities = $instance->loadAllUtilityClasses();

        // Auto-register all utilities in Registry if available
        if (class_exists('Registry')) {
            try {
                $registry = new Registry();

                foreach ($loadedUtilities as $className => $path) {
                    if (!class_exists($className)) {
                        continue;
                    }

                    $reflect = new ReflectionClass($className);
                    if ($reflect->isAbstract() || $reflect->isInterface() || $reflect->isTrait()) {
                        continue;
                    }

                    // Simple instantiation without constructor arguments
                    try {
                        $instanceObj = new $className();
                        $registry->set(lcfirst($className), $instanceObj);
                    } catch (Throwable $e) {
                        error_log("Failed to instantiate utility class $className: " . $e->getMessage());
                    }
                }
            } catch (Throwable $e) {
                error_log('Failed to instantiate Registry: ' . $e->getMessage());
            }
        } else {
            error_log('Registry class not found after core files load.');
        }

        return $instance;
    }

    private function initialize(): void
    {
        if ($this->isInitialized) {
            return;
        }

        $this->preventDirectAccess();
        $this->initializePaths();
        // config (MakingEnv) is already provided in constructor
        $this->defineApplicationConstants();
        $this->initializeSession();
        $this->configurePHP();
        $this->defineCoreFiles();

        $this->isInitialized = true;
    }

    private function preventDirectAccess(): void
    {
        if (!defined('FRAMEWORK_ENTRY')) {
            define('FRAMEWORK_ENTRY', true);
        }
    }

    private function initializePaths(): void
    {
        $dirs = ['brain', 'modules', 'storage'];

        foreach ($dirs as $dir) {
            $constName = 'DIR_' . strtoupper($dir);
            $bapaths = ROOT . DIRECTORY_SEPARATOR . $dir;

            if (!defined($constName)) {
                define($constName, $bapaths);
                $this->paths[$constName] = $bapaths;
            }
        }

        // Walk the folder tree and define constants for subfolders (useful shortcuts)
        foreach ($dirs as $dir) {
            $basePath = ROOT . DIRECTORY_SEPARATOR . $dir;

            if (!is_dir($basePath)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($basePath, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $dirItem) {
                if (!$dirItem->isDir()) continue;

                $realPath = $dirItem->getRealPath();
                if (!$realPath) continue;

                $const = 'DIR_' . strtoupper(
                    str_replace([ROOT . DIRECTORY_SEPARATOR, '/', '\\'], ['', '_', '_'], $realPath)
                );

                if (!defined($const)) {
                    define($const, $realPath);
                    $this->paths[$const] = $realPath;
                }
            }
        }

        if (defined('DIR_STORAGE_CACHE')) {
            @file_put_contents(
                DIR_STORAGE_CACHE . '/paths.json',
                json_encode($this->paths, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
        }
    }

    private function initializeMakingEnv(): void
    {
        // Left for backward compatibility if needed; not used because constructor already loaded env.
        $this->dbConfig = [
            'DB_DRIVER' => $this->config->get('DB_DRIVER', 'mysql'),
            'DB_HOST' => $this->config->get('DB_HOST', 'localhost'),
            'DB_PORT' => $this->config->get('DB_PORT', '3306'),
            'DB_USERNAME' => $this->config->get('DB_USERNAME', 'root'),
            'DB_PASSWORD' => $this->config->get('DB_PASSWORD', ''),
            'DB_DATABASE' => $this->config->get('DB_DATABASE', 'ct_frame'),
            'DB_PREFIX' => $this->config->get('DB_PREFIX', ''),
            'DB_CHARSET' => $this->config->get('DB_CHARSET', 'utf8mb4'),
            'DB_COLLATION' => $this->config->get('DB_COLLATION', 'utf8mb4_unicode_ci')
        ];

        $this->mailConfig = [
            'MAIL_DRIVER' => $this->config->get('MAIL_DRIVER', 'smtp'),
            'MAIL_HOST' => $this->config->get('MAIL_HOST', 'localhost'),
            'MAIL_PORT' => $this->config->get('MAIL_PORT', '587'),
            'MAIL_USERNAME' => $this->config->get('MAIL_USERNAME', ''),
            'MAIL_PASSWORD' => $this->config->get('MAIL_PASSWORD', ''),
            'MAIL_ENCRYPTION' => $this->config->get('MAIL_ENCRYPTION', 'tls'),
            'MAIL_FROM_ADDRESS' => $this->config->get('MAIL_FROM_ADDRESS', 'noreply@example.com'),
            'MAIL_FROM_NAME' => $this->config->get('MAIL_FROM_NAME', 'CyberTirah Framework')
        ];

        $this->defineConfigConstants();
    }

    private function defineConfigConstants(): void
    {
        foreach (array_merge($this->dbConfig, $this->mailConfig) as $constant => $value) {
            if (!defined($constant)) {
                define($constant, $value);
            }
        }
    }

    private function defineCoreFiles(): void
    {
        $this->coreFiles = [];
        $dirs = [];

        if (defined('DIR_BRAIN_CORE')) {
            $dirs[] = DIR_BRAIN_CORE;
        }

        $dirs[] = ROOT . DIRECTORY_SEPARATOR . 'brain' . DIRECTORY_SEPARATOR . 'Core';
        $dirs[] = ROOT . DIRECTORY_SEPARATOR . 'brain' . DIRECTORY_SEPARATOR . 'Classes' . DIRECTORY_SEPARATOR . 'core';

        $files = [];
        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                $found = glob(rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.php') ?: [];
                $files = array_merge($files, $found);
            }
        }

        foreach ($files as $file) {
            $key = pathinfo($file, PATHINFO_FILENAME);
            $this->coreFiles[$key] = $file;
        }

        if (defined('DIR_STORAGE_CACHE')) {
            @file_put_contents(
                DIR_STORAGE_CACHE . '/core_paths.json',
                json_encode($this->coreFiles, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
        }
    }

    public function safeRequire(string $file, bool $required = true): bool
    {
        if (!file_exists($file)) {
            if ($required) {
                $error = "Critical file not found: $file";
                error_log($error);
                http_response_code(500);
                throw new RuntimeException("Framework Error: Missing required file: " . basename($file));
            }
            return false;
        }

        try {
            require_once $file;
            return true;
        } catch (Throwable $e) {
            if ($required) {
                error_log("Error loading file $file: " . $e->getMessage());
                throw new RuntimeException("Framework Error: Failed to load required file: " . basename($file));
            }
            return false;
        }
    }

    public function loadCoreFiles(): array
    {
        $loadedFiles = [];
        $failedFiles = [];

        foreach ($this->coreFiles as $name => $file) {
            try {
                if ($this->safeRequire($file, true)) {
                    $loadedFiles[] = $name;
                }
            } catch (RuntimeException $e) {
                $failedFiles[] = $name;
                error_log("Failed to load core file: $name - " . $e->getMessage());
            }
        }

        if (!empty($failedFiles)) {
            throw new RuntimeException("Critical core files failed to load: " . implode(', ', $failedFiles));
        }

        return $loadedFiles;
    }

    /**
     * Scan and require all non-core utility classes found in DIR_BRAIN_CLASSES (and subfolders).
     * Returns an associative array of className => filePath.
     */
    public function loadAllUtilityClasses(): array
    {
        // return cached if already scanned
        if (!empty($this->loadedClasses)) {
            return $this->loadedClasses;
        }

        $roots = [];
        if (defined('DIR_BRAIN_CLASSES')) {
            $roots[] = DIR_BRAIN_CLASSES;
        }

        foreach ($roots as $root) {
            if (!is_dir($root)) continue;

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $fileInfo) {
                if ($fileInfo->isFile() && strtolower($fileInfo->getExtension()) === 'php') {
                    $path = $fileInfo->getRealPath();
                    if ($path) {
                        // We do not try to guess namespaces. We map basename => path for simple classes.
                        $className = $fileInfo->getBasename('.php');
                        try {
                            $this->safeRequire($path, true);
                            $this->loadedClasses[$className] = $path;
                        } catch (RuntimeException $e) {
                            // do not break loading of other utilities
                            error_log("Failed to require utility file: $path - " . $e->getMessage());
                        }
                    }
                }
            }
        }

        return $this->loadedClasses;
    }

    public function getLoadedUtilityClasses(): array
    {
        return $this->loadedClasses;
    }

    public function defineApplicationConstants(): void
    {
        if (!defined('APP_INSTANCE')) define('APP_INSTANCE', 'Y');
        if (!defined('ADMIN_PANEL')) define('ADMIN_PANEL', 'N');
        if (!defined('VERSION')) define('VERSION', '1.0.0');
        if (!defined('SITE_ICON')) define('SITE_ICON', $this->config->get('SITE_ICON', '/favicon.ico'));

        $tz = $this->config->get('APP_TIMEZONE', $this->config->get('TIMEZONE', 'UTC'));
        date_default_timezone_set($tz ?: 'UTC');

        if (defined('DIR_STORAGE_LOGS') && !defined('DIR_LOGS')) {
            define('DIR_LOGS', DIR_STORAGE_LOGS);
        }
        if (defined('DIR_STORAGE_CACHE') && !defined('DIR_CACHE')) {
            define('DIR_CACHE', DIR_STORAGE_CACHE);
        }

        $this->setCorsHeaders();
    }

    private function setCorsHeaders(): void
    {
        if (!headers_sent()) {
            $allowedOrigin = $this->config->get('CORS_ALLOWED_ORIGINS', $this->config->get('CORS_ORIGIN', '*'));
            header('Access-Control-Allow-Origin: ' . $allowedOrigin);
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            $allowedHeaders = $this->config->get('CORS_ALLOWED_HEADERS', 'Content-Type, Authorization, X-Requested-With');
            header('Access-Control-Allow-Headers: ' . $allowedHeaders);

            if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                http_response_code(200);
                exit();
            }
        }
    }

    public function initializeSession(): void
    {
        $useSession = filter_var($this->config->get('USE_SESSION', true), FILTER_VALIDATE_BOOLEAN);

        if (!$useSession) {
            return;
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        $forceHttps = filter_var($this->config->get('FORCE_HTTPS', false), FILTER_VALIDATE_BOOLEAN);

        session_set_cookie_params([
            'lifetime' => (int)$this->config->get('SESSION_LIFETIME', 0),
            'path'     => $this->config->get('SESSION_PATH', '/'),
            'domain'   => $this->config->get('SESSION_DOMAIN', ''),
            'secure'   => ($isHttps || $forceHttps),
            'httponly' => true,
            'samesite' => $this->config->get('SESSION_SAMESITE', 'Strict')
        ]);

        session_name($this->config->get('SESSION_NAME', 'CAFSESSID'));

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function configurePHP(): void
    {
        $devMode = filter_var($this->config->get('DEV_MODE', true), FILTER_VALIDATE_BOOLEAN);

        if ($devMode) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
            ini_set('log_errors', '1');
            ini_set('memory_limit', $this->config->get('MEMORY_LIMIT', '256M'));
            set_time_limit((int)$this->config->get('TIME_LIMIT', 300));

            ini_set('max_execution_time', (string)$this->config->get('MAX_EXECUTION_TIME', '300'));
            ini_set('max_input_time', (string)$this->config->get('MAX_INPUT_TIME', '300'));
            ini_set('post_max_size', $this->config->get('POST_MAX_SIZE', '50M'));
            ini_set('upload_max_filesize', $this->config->get('UPLOAD_MAX_FILESIZE', '50M'));
        } else {
            error_reporting(0);
            ini_set('display_errors', '0');
        }
    }

    public function getExecutionTime(): float
    {
        return microtime(true) - $this->startTime;
    }
}

// Ensure $bootstrap exists (use existing or boot)
if (!isset($bootstrap) && class_exists('Bootstrap')) {
    $bootstrap = Bootstrap::boot();
}

// load map of utilities (path map: basename => path)
$loadedMap = [];
if (isset($bootstrap) && method_exists($bootstrap, 'loadAllUtilityClasses')) {
    $loadedMap = $bootstrap->loadAllUtilityClasses();
} elseif (isset($bootstrap) && method_exists($bootstrap, 'getLoadedUtilityClasses')) {
    $loadedMap = $bootstrap->getLoadedUtilityClasses();
}

if (class_exists('Registry')) {
    try {
        $registry = new Registry();
    } catch (Throwable $e) {
        error_log('Failed to instantiate Registry: ' . $e->getMessage());
        $registry = null;
    }
} else {
    error_log('Registry class not found after core files load.');
    $registry = null;
}

if ($registry && !empty($loadedMap)) {
    $fileToClasses = [];
    foreach (get_declared_classes() as $decl) {
        try {
            $rc = new ReflectionClass($decl);
        } catch (ReflectionException $e) {
            continue;
        }
        $file = $rc->getFileName();
        if ($file) {
            $rp = realpath($file) ?: $file;
            $fileToClasses[$rp][] = $decl;
        }
    }

    $candidates = [];
    foreach ($loadedMap as $basename => $path) {
        $rp = realpath($path) ?: $path;
        $classes = $fileToClasses[$rp] ?? [];
        foreach ($classes as $fqcn) {
            try {
                $rc = new ReflectionClass($fqcn);
            } catch (ReflectionException $e) {
                continue;
            }
            if ($rc->isInstantiable()) {
                $candidates[$fqcn] = $rc;
            }
        }
    }

    $tryInstantiate = function (ReflectionClass $rc, $registry) {
        $ctor = $rc->getConstructor();
        if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
            try { return $rc->newInstance(); } catch (Throwable $e) { return null; }
        }

        $params = $ctor->getParameters();
        $args = [];

        foreach ($params as $p) {
            $t = $p->getType();

            if ($t instanceof ReflectionNamedType && !$t->isBuiltin()) {
                $paramClass = $t->getName();
                $paramShort = lcfirst((new ReflectionClass($paramClass))->getShortName());

                $dep = null;
                if (method_exists($registry, 'get')) {
                    try {
                        $dep = $registry->get($paramShort);
                        if ($dep === null) {
                            $dep = $registry->get($paramClass);
                        }
                    } catch (Throwable $e) {
                        $dep = null;
                    }
                }
                if ($dep === null && property_exists($registry, $paramShort)) {
                    $dep = $registry->{$paramShort};
                }
                if ($dep === null && class_exists($paramClass)) {
                    try {
                        $depRc = new ReflectionClass($paramClass);
                        if ($depRc->isInstantiable()) {
                            $depCtor = $depRc->getConstructor();
                            if (!$depCtor || $depCtor->getNumberOfRequiredParameters() === 0) {
                                $dep = $depRc->newInstance();
                            }
                        }
                    } catch (Throwable $e) {
                        $dep = null;
                    }
                }

                if ($dep === null) {
                    return null;
                }
                $args[] = $dep;
            } else {
                if ($p->isDefaultValueAvailable()) {
                    $args[] = $p->getDefaultValue();
                } else {
                    return null;
                }
            }
        }

        try {
            return $rc->newInstanceArgs($args);
        } catch (Throwable $e) {
            return null;
        }
    };

    $pending = $candidates;
    $registered = [];
    $maxPasses = 4;
    $pass = 0;

    while (!empty($pending) && $pass < $maxPasses) {
        foreach ($pending as $fqcn => $rc) {
            $short = lcfirst($rc->getShortName());
            $exists = false;
            if (method_exists($registry, 'get')) {
                try { $exists = $registry->get($short) !== null; } catch (Throwable $_) { $exists = false; }
            } elseif (property_exists($registry, $short)) {
                $exists = isset($registry->{$short});
            }

            if ($exists) {
                unset($pending[$fqcn]);
                continue;
            }

            $obj = null;

            $ctor = $rc->getConstructor();
            if ($ctor && $ctor->getNumberOfParameters() === 1) {
                $p = $ctor->getParameters()[0];
                $t = $p->getType();
                if ($t instanceof ReflectionNamedType && !$t->isBuiltin() && $t->getName() === Registry::class) {
                    try { $obj = $rc->newInstance($registry); } catch (Throwable $e) { $obj = null; }
                }
            }

            if ($obj === null) {
                $obj = $tryInstantiate($rc, $registry);
            }

            if ($obj !== null) {
                $key = lcfirst($rc->getShortName());
                if (method_exists($registry, 'set')) {
                    try {
                        $registry->set($key, $obj);
                    } catch (Throwable $e) {
                        error_log("Registry::set failed for {$key}: " . $e->getMessage());
                    }
                } else {
                    $registry->{$key} = $obj;
                }

                $registered[$fqcn] = $key;
                unset($pending[$fqcn]);
            }
        }
        $pass++;
    }

    if (!empty($pending)) {
        $notRegistered = array_keys($pending);
        error_log('Some utility classes could not be auto-instantiated: ' . implode(', ', array_map(fn($c)=> (string)$c, $notRegistered)));
    }

    $GLOBALS['registry'] = $registry;
}
