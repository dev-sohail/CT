<?php

declare(strict_types=1);

/**
 * Router Class
 * 
 * Enhanced routing system with caching, named routes, groups, and middleware support
 * Modules are organized as: DIR_BODY/roles(admin, public, etc)/(Module Name)/(Controllers, Models, Views, routes.json)
 * 
 * @version 2.0.0
 */
final class Router
{
    /**
     * Registered routes
     * 
     * @var array<array{method: string, path: string, handler: callable|string, middleware: array, name: string|null, params: array}>
     */
    protected static array $routes = [];

    /**
     * Named routes registry
     * 
     * @var array<string, array>
     */
    protected static array $namedRoutes = [];

    /**
     * Route groups stack
     * 
     * @var array<array{prefix: string, middleware: array, name: string}>
     */
    protected static array $groupStack = [];

    /**
     * 404 Not Found handler
     * 
     * @var callable|null
     */
    protected static $notFound = null;

    /**
     * Global middleware stack
     * 
     * @var array<callable|string>
     */
    protected static array $middleware = [];

    /**
     * Route cache
     * 
     * @var array|null
     */
    protected static ?array $routeCache = null;

    /**
     * Cache file path
     * 
     * @var string|null
     */
    protected static ?string $cacheFile = null;

    /**
     * Enable/disable route caching
     * 
     * @var bool
     */
    protected static bool $cacheEnabled = true;

    /**
     * Route log file path
     * 
     * @var string|null
     */
    protected static ?string $logFile = null;

    /**
     * Route logging enabled flag
     * 
     * @var bool
     */
    protected static bool $logEnabled = true;

    /**
     * Current route being executed
     * 
     * @var array|null
     */
    protected static ?array $currentRoute = null;

    /**
     * Enable route caching
     * 
     * @param string|null $cacheFile Path to cache file
     */
    public static function enableCache(?string $cacheFile = null): void
    {
        self::$cacheEnabled = true;
        if ($cacheFile) {
            self::$cacheFile = $cacheFile;
        } elseif (defined('DIR_STORAGE_CACHE')) {
            self::$cacheFile = DIR_STORAGE_CACHE . '/routes.php';
        }
    }

    /**
     * Disable route caching
     */
    public static function disableCache(): void
    {
        self::$cacheEnabled = false;
    }

    /**
     * Clear route cache
     */
    public static function clearCache(): void
    {
        self::$routeCache = null;
        if (self::$cacheFile && file_exists(self::$cacheFile)) {
            @unlink(self::$cacheFile);
        }
    }

    /**
     * Register a GET route
     * 
     * @param string $path The route path
     * @param callable|string $handler The route handler
     * @param array $options Optional: middleware, name
     */
    public static function get(string $path, $handler, array $options = []): void
    {
        self::addRoute('GET', $path, $handler, $options);
    }

    /**
     * Register a POST route
     * 
     * @param string $path The route path
     * @param callable|string $handler The route handler
     * @param array $options Optional: middleware, name
     */
    public static function post(string $path, $handler, array $options = []): void
    {
        self::addRoute('POST', $path, $handler, $options);
    }

    /**
     * Register a PUT route
     * 
     * @param string $path The route path
     * @param callable|string $handler The route handler
     * @param array $options Optional: middleware, name
     */
    public static function put(string $path, $handler, array $options = []): void
    {
        self::addRoute('PUT', $path, $handler, $options);
    }

    /**
     * Register a DELETE route
     * 
     * @param string $path The route path
     * @param callable|string $handler The route handler
     * @param array $options Optional: middleware, name
     */
    public static function delete(string $path, $handler, array $options = []): void
    {
        self::addRoute('DELETE', $path, $handler, $options);
    }

    /**
     * Register a PATCH route
     * 
     * @param string $path The route path
     * @param callable|string $handler The route handler
     * @param array $options Optional: middleware, name
     */
    public static function patch(string $path, $handler, array $options = []): void
    {
        self::addRoute('PATCH', $path, $handler, $options);
    }

    /**
     * Register a route for any HTTP method
     * 
     * @param string $path The route path
     * @param callable|string $handler The route handler
     * @param array $options Optional: middleware, name
     */
    public static function any(string $path, $handler, array $options = []): void
    {
        foreach (['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD'] as $method) {
            self::addRoute($method, $path, $handler, $options);
        }
    }

    /**
     * Register a route group
     * 
     * @param array $attributes Group attributes (prefix, middleware, name)
     * @param callable $callback Group routes callback
     */
    public static function group(array $attributes, callable $callback): void
    {
        $prefix = $attributes['prefix'] ?? '';
        $middleware = $attributes['middleware'] ?? [];
        $name = $attributes['name'] ?? '';

        self::$groupStack[] = [
            'prefix' => $prefix,
            'middleware' => is_array($middleware) ? $middleware : [$middleware],
            'name' => $name
        ];

        call_user_func($callback);

        array_pop(self::$groupStack);
    }

    /**
     * Set a 404 handler
     * 
     * @param callable $handler The 404 handler
     */
    public static function setNotFound(callable $handler): void
    {
        self::$notFound = $handler;
    }

    /**
     * Add global middleware
     * 
     * @param callable|string $middleware The middleware function or class name
     */
    public static function middleware($middleware): void
    {
        self::$middleware[] = $middleware;
    }

    /**
     * Add a route to the registry
     * 
     * @param string $method HTTP method
     * @param string $path Route path
     * @param callable|string $handler Route handler
     * @param array $options Route options (middleware, name)
     */
    protected static function addRoute(string $method, string $path, $handler, array $options = []): void
    {
        // Get current group attributes
        $groupPrefix = '';
        $groupMiddleware = [];
        $groupName = '';
        
        foreach (self::$groupStack as $group) {
            $groupPrefix .= $group['prefix'];
            $groupMiddleware = array_merge($groupMiddleware, $group['middleware']);
            $groupName .= $group['name'];
        }

        // Merge group and route middleware
        $middleware = $groupMiddleware;
        if (isset($options['middleware'])) {
            $routeMiddleware = is_array($options['middleware']) ? $options['middleware'] : [$options['middleware']];
            $middleware = array_merge($middleware, $routeMiddleware);
        }

        // Build full path
        $fullPath = self::normalizePath($groupPrefix . $path);

        // Build full name
        $routeName = $groupName . ($options['name'] ?? '');

        $route = [
            'method' => strtoupper($method),
            'path' => $fullPath,
            'original_path' => $fullPath,
            'handler' => $handler,
            'middleware' => $middleware,
            'name' => $routeName ?: null,
            'params' => []
        ];

        self::$routes[] = $route;

        // Register named route
        if ($routeName) {
            self::$namedRoutes[$routeName] = $route;
        }
    }

    /**
     * Resolve a handler definition which may be a callable or a string like
     * "role/module/Controller@method" or "Controller@method".
     *
     * @param callable|string $handler
     * @param array $params Route parameters
     * @return callable
     */
    private static function resolveHandler($handler, array $params = []): callable
    {
        if (is_callable($handler)) {
            return function() use ($handler, $params) {
                return call_user_func_array($handler, $params);
            };
        }

        if (is_string($handler)) {
            // Supported formats:
            // - "admin/Blog/BlogController@index" (role/module/controller@method)
            // - "BlogController@index" (controller@method)
            // - "Controller@method"

            if (!str_contains($handler, '@')) {
                throw new InvalidArgumentException("Invalid handler format: $handler. Expected 'Controller@method' or 'role/module/Controller@method'");
            }

            $parts = explode('@', $handler, 2);
            $classPart = trim($parts[0]);
            $method = trim($parts[1] ?? 'index');

            if (empty($classPart) || empty($method)) {
                throw new InvalidArgumentException("Invalid handler format: $handler");
            }

            $role = null;
            $module = null;
            $controller = null;

            // Parse role/module/controller format
            if (str_contains($classPart, '/')) {
                $segments = explode('/', $classPart);
                if (count($segments) === 3) {
                    [$role, $module, $controller] = $segments;
                } else {
                    throw new InvalidArgumentException("Invalid handler format: $handler. Expected 'role/module/Controller@method'");
                }
            } else {
                $controller = $classPart;
            }

            return self::createControllerCallable($role, $module, $controller, $method, $params);
        }

        throw new InvalidArgumentException('Invalid route handler definition');
    }

    /**
     * Create a callable for controller execution
     * 
     * @param string|null $role Role name
     * @param string|null $module Module name
     * @param string $controller Controller name
     * @param string $method Method name
     * @param array $params Route parameters
     * @return callable
     */
    private static function createControllerCallable(?string $role, ?string $module, string $controller, string $method, array $params): callable
    {
        return function () use ($role, $module, $controller, $method, $params) {
            $instance = self::loadController($role, $module, $controller);

            if (!method_exists($instance, $method)) {
                throw new RuntimeException("Method $method not found in controller " . get_class($instance));
            }

            try {
                // Call method with route parameters
                return call_user_func_array([$instance, $method], $params);
            } catch (Throwable $e) {
                error_log("Controller method error in " . get_class($instance) . "::{$method}: " . $e->getMessage());
                throw $e;
            }
        };
    }

    /**
     * Load and instantiate a controller
     * 
     * @param string|null $role Role name
     * @param string|null $module Module name
     * @param string $controller Controller name
     * @return object Controller instance
     * @throws RuntimeException If controller not found
     */
    private static function loadController(?string $role, ?string $module, string $controller): object
    {
        // Check if base Controller class exists
                if (!class_exists('Controller')) {
                    throw new RuntimeException('Base Controller class not loaded');
                }

        // Try to load controller file if role/module provided
                if ($role && $module) {
            $base = defined('DIR_BODY') ? DIR_BODY : (defined('BODY_DIR') ? BODY_DIR : (defined('ROOT') ? ROOT . DIRECTORY_SEPARATOR . 'Body' : null));
            
            if (!$base) {
                throw new RuntimeException('Body directory not defined');
            }

            // Try multiple file name variations
                    $fileVariations = [
                $controller . '.php',  // HeaderController.php
                lcfirst($controller) . '.php',  // headerController.php
                strtolower($controller) . '.php',  // headercontroller.php
                strtolower(preg_replace('/Controller$/', '', $controller)) . '.php',  // header.php
                preg_replace('/Controller$/', '', $controller) . '.php',  // Header.php
            ];

            $loaded = false;
            foreach ($fileVariations as $filename) {
                $controllerFile = $base . DIRECTORY_SEPARATOR . $role . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . $filename;
                
                if (file_exists($controllerFile) && is_readable($controllerFile)) {
                    require_once $controllerFile;
                    $loaded = true;
                                break;
                }

                // Try lowercase controllers directory
                $controllerFile = $base . DIRECTORY_SEPARATOR . $role . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . $filename;
                if (file_exists($controllerFile) && is_readable($controllerFile)) {
                    require_once $controllerFile;
                    $loaded = true;
                    break;
                }
            }

            if (!$loaded) {
                throw new RuntimeException("Controller file not found: $controller in $role/$module/Controllers/");
            }
        }

        // Try to instantiate controller
        $registry = $GLOBALS['registry'] ?? (class_exists('Registry') ? Registry::getInstance() : null);
        
        // Try different class name patterns
        $classNames = [
            $controller,
            ucfirst($controller),
            $controller . 'Controller',
            ucfirst($controller) . 'Controller',
            ucfirst(preg_replace('/Controller$/', '', $controller)) . 'Controller'
        ];

        foreach ($classNames as $className) {
            if (class_exists($className)) {
                try {
                    return new $className($registry);
                } catch (Throwable $e) {
                    // Try without registry parameter
                    try {
                        return new $className();
                    } catch (Throwable $e2) {
                        error_log("Failed to instantiate controller $className: " . $e2->getMessage());
                        continue;
                    }
                }
            }
        }

        throw new RuntimeException("Controller class not found: $controller (tried: " . implode(', ', $classNames) . ")");
    }

    /**
     * Normalize route path
     * 
     * @param string $path The route path
     * @return string Normalized path
     */
    private static function normalizePath(string $path): string
    {
        $path = trim($path);
        $path = rtrim($path, '/');
        return $path ?: '/';
    }

    /**
     * Check if a route matches the given path
     * 
     * @param string $routePath The route path pattern
     * @param string $requestPath The actual request path
     * @return array|false Route parameters or false if no match
     */
    private static function matchRoute(string $routePath, string $requestPath): array|false
    {
        // Exact match (most common case, check first)
        if ($routePath === $requestPath) {
            return [];
        }

        // Check for dynamic parameters like {id}, {slug}, {id:\d+}
        if (str_contains($routePath, '{')) {
            $pattern = $routePath;
            $paramNames = [];
            
            // Replace {param} or {param:regex} with capture groups
            $pattern = preg_replace_callback('/\{([^:}]+)(?::([^}]+))?\}/', function ($matches) use (&$paramNames) {
                $paramNames[] = $matches[1];
                $regex = $matches[2] ?? '[^/]+';
                return '(' . $regex . ')';
            }, $pattern);
            
            $pattern = '#^' . $pattern . '$#';
            
            if (preg_match($pattern, $requestPath, $matches)) {
                array_shift($matches); // Remove full match
                
                $params = [];
                foreach ($paramNames as $index => $name) {
                    $params[$name] = $matches[$index] ?? null;
                }
                
                return $params;
            }
        }

        return false;
    }

    /**
     * Dispatch the request
     * 
     * @param string|null $method Override HTTP method
     * @param string|null $uri Override request URI
     */
    public static function run(?string $method = null, ?string $uri = null): void
    {
        try {
            $uri = $uri ?? self::getCurrentUri();
            $method = $method ?? $_SERVER['REQUEST_METHOD'] ?? 'GET';

            // Try to use cached routes
            if (self::$cacheEnabled && self::$routeCache === null && self::$cacheFile) {
                self::loadCachedRoutes();
            }

        // Find matching route
        $route = self::findRoute($method, $uri);
        
        if ($route) {
                self::$currentRoute = $route;
            self::executeRoute($route);
        } else {
            self::handleNotFound();
            }
        } catch (Throwable $e) {
            self::handleError($e);
        }
    }

    /**
     * Load cached routes
     */
    private static function loadCachedRoutes(): void
    {
        if (self::$cacheFile && file_exists(self::$cacheFile)) {
            try {
                self::$routeCache = require self::$cacheFile;
                if (is_array(self::$routeCache) && isset(self::$routeCache['routes'])) {
                    self::$routes = self::$routeCache['routes'];
                    self::$namedRoutes = self::$routeCache['named'] ?? [];
                }
            } catch (Throwable $e) {
                error_log("Failed to load route cache: " . $e->getMessage());
                self::$routeCache = null;
            }
        }
    }

    /**
     * Save routes to cache
     */
    public static function saveCache(): void
    {
        if (!self::$cacheEnabled || !self::$cacheFile) {
            return;
        }

        try {
            $cacheDir = dirname(self::$cacheFile);
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }

            $cacheData = [
                'routes' => self::$routes,
                'named' => self::$namedRoutes,
                'generated_at' => time()
            ];

            $content = "<?php\n\nreturn " . var_export($cacheData, true) . ";\n";
            file_put_contents(self::$cacheFile, $content, LOCK_EX);
        } catch (Throwable $e) {
            error_log("Failed to save route cache: " . $e->getMessage());
        }
    }

    /**
     * Enable route logging
     * 
     * @param string|null $logFile Optional custom log file path
     */
    public static function enableLogging(?string $logFile = null): void
    {
        self::$logEnabled = true;
        self::$logFile = $logFile ?? (defined('ROOT') ? ROOT . '/storage/logs/all_routes.json' : __DIR__ . '/../../storage/logs/all_routes.json');
    }

    /**
     * Disable route logging
     */
    public static function disableLogging(): void
    {
        self::$logEnabled = false;
    }

    /**
     * Log all routes to JSON file
     * 
     * @param bool $force Force logging even if disabled
     * @return bool True if logged successfully
     */
    public static function logRoutes(bool $force = false): bool
    {
        if (!$force && !self::$logEnabled) {
            return false;
        }

        try {
            // Set default log file if not set
            if (!self::$logFile) {
                self::$logFile = defined('ROOT') ? ROOT . '/storage/logs/all_routes.json' : __DIR__ . '/../../storage/logs/all_routes.json';
            }

            $logDir = dirname(self::$logFile);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }

            // Prepare route data for logging
            $routeData = [];
            $totalRoutes = 0;

            foreach (self::$routes as $method => $routes) {
                foreach ($routes as $pattern => $route) {
                    $routeData[] = [
                        'method' => strtoupper($method),
                        'path' => $pattern,
                        'handler' => is_string($route['handler']) ? $route['handler'] : 'Closure',
                        'name' => $route['name'] ?? null,
                        'middleware' => $route['middleware'] ?? [],
                        'params' => $route['params'] ?? []
                    ];
                    $totalRoutes++;
                }
            }

            // Sort routes by method and path
            usort($routeData, function($a, $b) {
                $methodOrder = ['GET' => 0, 'POST' => 1, 'PUT' => 2, 'DELETE' => 3, 'PATCH' => 4];
                $aOrder = $methodOrder[$a['method']] ?? 999;
                $bOrder = $methodOrder[$b['method']] ?? 999;
                
                if ($aOrder !== $bOrder) {
                    return $aOrder - $bOrder;
                }
                
                return strcmp($a['path'], $b['path']);
            });

            // Prepare log structure
            $logData = [
                'meta' => [
                    'framework' => 'CyberTirah Framework',
                    'version' => '2.0.0',
                    'generated_at' => date('Y-m-d H:i:s'),
                    'timestamp' => time(),
                    'total_routes' => $totalRoutes,
                    'total_named_routes' => count(self::$namedRoutes),
                    'methods' => array_keys(self::$routes),
                    'environment' => getenv('APP_ENV') ?: 'production'
                ],
                'statistics' => [
                    'by_method' => [],
                    'with_middleware' => 0,
                    'with_names' => 0,
                    'with_params' => 0
                ],
                'named_routes' => self::$namedRoutes,
                'routes' => $routeData
            ];

            // Calculate statistics
            foreach ($routeData as $route) {
                $method = $route['method'];
                if (!isset($logData['statistics']['by_method'][$method])) {
                    $logData['statistics']['by_method'][$method] = 0;
                }
                $logData['statistics']['by_method'][$method]++;

                if (!empty($route['middleware'])) {
                    $logData['statistics']['with_middleware']++;
                }
                if (!empty($route['name'])) {
                    $logData['statistics']['with_names']++;
                }
                if (!empty($route['params'])) {
                    $logData['statistics']['with_params']++;
                }
            }

            // Write to file with pretty formatting
            $json = json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            file_put_contents(self::$logFile, $json, LOCK_EX);

            return true;
        } catch (Throwable $e) {
            error_log("Failed to log routes: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get route statistics
     * 
     * @return array Route statistics
     */
    public static function getRouteStatistics(): array
    {
        $stats = [
            'total_routes' => 0,
            'by_method' => [],
            'with_middleware' => 0,
            'with_names' => 0,
            'named_routes' => count(self::$namedRoutes)
        ];

        foreach (self::$routes as $method => $routes) {
            $methodUpper = strtoupper($method);
            $stats['by_method'][$methodUpper] = count($routes);
            $stats['total_routes'] += count($routes);

            foreach ($routes as $route) {
                if (!empty($route['middleware'])) {
                    $stats['with_middleware']++;
                }
                if (!empty($route['name'])) {
                    $stats['with_names']++;
                }
            }
        }

        return $stats;
    }

    /**
     * Clear route log file
     */
    public static function clearLog(): void
    {
        if (self::$logFile && file_exists(self::$logFile)) {
            @unlink(self::$logFile);
        }
    }

    /**
     * Get current request URI
     * 
     * @return string The current URI
     */
    private static function getCurrentUri(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        return self::normalizePath($uri);
    }

    /**
     * Find matching route
     * 
     * @param string $method HTTP method
     * @param string $uri Request URI
     * @return array|null Matching route or null
     */
    private static function findRoute(string $method, string $uri): ?array
    {
        foreach (self::$routes as $index => $route) {
            $params = self::matchesRoute($route, $method, $uri);
            if ($params !== false) {
                $route['params'] = $params;
                $route['index'] = $index;
                return $route;
            }
        }
        return null;
    }

    /**
     * Check if route matches request
     * 
     * @param array $route Route definition
     * @param string $method HTTP method
     * @param string $uri Request URI
     * @return array|false Route parameters or false if no match
     */
    private static function matchesRoute(array $route, string $method, string $uri): array|false
    {
        // Check HTTP method
        if ($route['method'] !== $method) {
            return false;
        }

        // Check path with dynamic parameter support
        $params = self::matchRoute($route['path'], $uri);
        if ($params !== false) {
            return $params;
        }

        return false;
    }

    /**
     * Execute route with middleware
     * 
     * @param array $route Route definition
     */
    private static function executeRoute(array $route): void
    {
        try {
            // Extract route parameters
            $params = $route['params'] ?? [];
            
            // Store parameters in $_GET and a dedicated array for easy access
            foreach ($params as $key => $value) {
                $_GET[$key] = $value;
            }

            // Execute global middleware
            foreach (self::$middleware as $middleware) {
                $result = self::executeMiddleware($middleware);
                if ($result === false) {
                    return; // Middleware stopped execution
                }
            }

            // Execute route-specific middleware
            foreach ($route['middleware'] as $middleware) {
                $result = self::executeMiddleware($middleware);
                if ($result === false) {
                    return; // Middleware stopped execution
                }
            }

            // Resolve and execute route handler
            $handler = self::resolveHandler($route['handler'], array_values($params));
            call_user_func($handler);

        } catch (Throwable $e) {
            self::handleError($e);
        }
    }

    /**
     * Execute middleware
     * 
     * @param callable|string $middleware Middleware function or class name
     * @return mixed Middleware result
     */
    private static function executeMiddleware($middleware)
    {
        // If middleware is a class name string
        if (is_string($middleware) && class_exists($middleware)) {
            $instance = new $middleware();
            if (method_exists($instance, 'handle')) {
                return $instance->handle();
            }
            if (is_callable($instance)) {
                return call_user_func($instance);
            }
            throw new RuntimeException("Middleware class $middleware must have a handle() method or be callable");
        }

        // If middleware is callable
        if (is_callable($middleware)) {
        return call_user_func($middleware);
        }

        throw new InvalidArgumentException("Invalid middleware type");
    }

    /**
     * Handle 404 Not Found
     */
    private static function handleNotFound(): void
    {
        http_response_code(404);

        if (self::$notFound) {
            call_user_func(self::$notFound);
        } else {
            self::defaultNotFound();
        }
    }

    /**
     * Default 404 handler
     */
    private static function defaultNotFound(): void
    {
        // Try to load custom 404 page
        $notFoundPath = defined('ROOT') ? ROOT . '/Body/public/Error/Views/404.ct' : __DIR__ . '/../../Body/public/Error/Views/404.ct';
        
        if (file_exists($notFoundPath)) {
            // Get available routes for dev mode
            $availableRoutes = [];
            if (defined('DEV_MODE') && (int)getenv('DEV_MODE') === 1) {
                foreach (self::$routes as $method => $routes) {
                    foreach ($routes as $pattern => $route) {
                        $name = isset($route['name']) ? " ({$route['name']})" : '';
                        $availableRoutes[] = strtoupper($method) . ' ' . $pattern . $name;
                    }
                }
            }
            
            include $notFoundPath;
        } else {
            // Fallback inline 404 page
            $uri = htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/');
            echo '<div style="margin: 2rem auto; max-width: 600px; padding: 2rem; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 0.5rem; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif;">';
            echo '<h2 style="color: #dc3545; margin-top: 0;">404 Not Found</h2>';
            echo '<p style="color: #6c757d; margin-bottom: 1rem;">The requested page could not be found.</p>';
            echo '<p style="color: #6c757d; margin-bottom: 0; font-size: 0.9em;"><strong>URI:</strong> ' . $uri . '</p>';
            echo '</div>';
        }
    }

    /**
     * Handle errors
     * 
     * @param Throwable $e The exception
     */
    private static function handleError(Throwable $e): void
    {
        http_response_code(500);
        error_log("Router error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        
        $isDevelopment = (defined('APP_DEBUG') && APP_DEBUG) || 
                         (defined('APP_ENV') && APP_ENV === 'development') ||
                         (defined('DEV_MODE') && DEV_MODE);
        
        if ($isDevelopment) {
            echo '<div style="margin: 2rem auto; max-width: 800px; padding: 2rem; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 0.5rem; font-family: monospace; font-size: 14px;">';
            echo '<h2 style="color: #dc3545; margin-top: 0;">500 Internal Server Error</h2>';
            echo '<p style="color: #6c757d; margin-bottom: 1rem;"><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p style="color: #6c757d; margin-bottom: 1rem;"><strong>File:</strong> ' . htmlspecialchars($e->getFile()) . ' (Line: ' . $e->getLine() . ')</p>';
            echo '<details style="margin-top: 1rem;"><summary style="cursor: pointer; color: #495057; font-weight: bold;">Stack Trace</summary>';
            echo '<pre style="background: #e9ecef; padding: 1rem; margin-top: 0.5rem; border-radius: 0.25rem; overflow-x: auto;">' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
            echo '</details>';
            echo '</div>';
        } else {
            echo '<div style="margin: 2rem auto; max-width: 600px; padding: 2rem; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 0.5rem; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif;">';
            echo '<h2 style="color: #dc3545; margin-top: 0;">500 Internal Server Error</h2>';
            echo '<p style="color: #6c757d; margin-bottom: 0;">An error occurred while processing your request.</p>';
            echo '</div>';
        }
    }

    /**
     * Get all registered routes
     * 
     * @return array All routes
     */
    public static function getRoutes(): array
    {
        return self::$routes;
    }

    /**
     * Get named routes
     * 
     * @return array Named routes
     */
    public static function getNamedRoutes(): array
    {
        return self::$namedRoutes;
    }

    /**
     * Get current route
     * 
     * @return array|null Current route being executed
     */
    public static function getCurrentRoute(): ?array
    {
        return self::$currentRoute;
    }

    /**
     * Clear all routes
     */
    public static function clearRoutes(): void
    {
        self::$routes = [];
        self::$namedRoutes = [];
        self::$groupStack = [];
    }

    /**
     * Get URL for named route
     * 
     * @param string $name Route name
     * @param array $params Route parameters
     * @return string Generated URL
     * @throws RuntimeException If route not found
     */
    public static function url(string $name, array $params = []): string
    {
        if (!isset(self::$namedRoutes[$name])) {
            throw new RuntimeException("Named route not found: $name");
        }

        $route = self::$namedRoutes[$name];
        $path = $route['path'];

        // Replace parameters in path
        foreach ($params as $key => $value) {
            // Handle both {param} and {param:regex} formats
            $path = preg_replace('/\{' . preg_quote($key, '/') . '(?::[^}]+)?\}/', (string)$value, $path);
        }

        // Check if all parameters were replaced
        if (preg_match('/\{[^}]+\}/', $path)) {
            throw new RuntimeException("Missing required parameters for route: $name");
        }

        return $path;
    }

    /**
     * Check if named route exists
     * 
     * @param string $name Route name
     * @return bool True if route exists
     */
    public static function hasRoute(string $name): bool
    {
        return isset(self::$namedRoutes[$name]);
    }

    /**
     * Load routes from JSON file
     * 
     * @param string $filePath Path to JSON routes file
     * @return bool True if loaded successfully
     */
    public static function loadFromJson(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            error_log("Route file not found: $filePath");
            return false;
        }

        try {
        $content = file_get_contents($filePath);
        if ($content === false) {
                error_log("Failed to read route file: $filePath");
                return false;
            }

            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Invalid JSON in route file $filePath: " . json_last_error_msg());
                return false;
            }

            if (!is_array($data) || !isset($data['routes'])) {
                error_log("Invalid route structure in file: $filePath");
                return false;
            }

            foreach ($data['routes'] as $route) {
                if (!isset($route['method'], $route['path'], $route['handler'])) {
                    error_log("Invalid route definition in $filePath: missing required fields");
                    continue;
                }

                $method = strtolower(trim($route['method']));
                $path = trim($route['path']);
                $handler = $route['handler'];

                // Build options
                $options = [];
                if (isset($route['middleware'])) {
                    $options['middleware'] = $route['middleware'];
                }
                if (isset($route['name'])) {
                    $options['name'] = $route['name'];
                }

                // Register route
                if (method_exists(self::class, $method)) {
                    self::{$method}($path, $handler, $options);
                } else {
                    error_log("Unsupported HTTP method in $filePath: $method");
                }
            }

            return true;
        } catch (Throwable $e) {
            error_log("Error loading routes from $filePath: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Redirect to URL
     * 
     * @param string $url URL to redirect to
     * @param int $statusCode HTTP status code (default: 302)
     */
    public static function redirect(string $url, int $statusCode = 302): void
    {
        if (!headers_sent()) {
            header("Location: $url", true, $statusCode);
            exit();
        }
        throw new RuntimeException("Cannot redirect, headers already sent");
    }

    /**
     * Redirect to named route
     * 
     * @param string $name Route name
     * @param array $params Route parameters
     * @param int $statusCode HTTP status code (default: 302)
     */
    public static function redirectToRoute(string $name, array $params = [], int $statusCode = 302): void
    {
        $url = self::url($name, $params);
        self::redirect($url, $statusCode);
    }

    /**
     * Get route statistics
     * 
     * @return array Route statistics
     */
    public static function getStats(): array
    {
        $methods = [];
        foreach (self::$routes as $route) {
            $method = $route['method'];
            $methods[$method] = ($methods[$method] ?? 0) + 1;
        }

        return [
            'total_routes' => count(self::$routes),
            'named_routes' => count(self::$namedRoutes),
            'methods' => $methods,
            'cache_enabled' => self::$cacheEnabled,
            'cache_file' => self::$cacheFile
        ];
    }
}
