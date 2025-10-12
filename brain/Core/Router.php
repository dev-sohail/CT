<?php

declare(strict_types=1);

/**
 * Router Class
 * 
 * Handles HTTP routing for the framework
 * Modules are organized as: DIR_BODY/roles(admin, public, etc)/(Module Name)/(Controllers, Models, Views, routes.json)
 */
final class Router
{
    /**
     * Registered routes
     * 
     * @var array<array{method: string, path: string, handler: callable, middleware?: callable[]}>
     */
    protected static array $routes = [];

    /**
     * 404 Not Found handler
     */
    protected static $notFound = null;

    /**
     * Global middleware stack
     * 
     * @var callable[]
     */
    protected static array $middleware = [];

    /**
     * Register a GET route
     * 
     * @param string $path The route path
     * @param callable $handler The route handler
     * @param callable[] $middleware Optional middleware for this route
     */
    public static function get(string $path, callable $handler, array $middleware = []): void
    {
        self::addRoute('GET', $path, $handler, $middleware);
    }

    /**
     * Register a POST route
     * 
     * @param string $path The route path
     * @param callable $handler The route handler
     * @param callable[] $middleware Optional middleware for this route
     */
    public static function post(string $path, callable $handler, array $middleware = []): void
    {
        self::addRoute('POST', $path, $handler, $middleware);
    }

    /**
     * Register a PUT route
     * 
     * @param string $path The route path
     * @param callable $handler The route handler
     * @param callable[] $middleware Optional middleware for this route
     */
    public static function put(string $path, callable $handler, array $middleware = []): void
    {
        self::addRoute('PUT', $path, $handler, $middleware);
    }

    /**
     * Register a DELETE route
     * 
     * @param string $path The route path
     * @param callable $handler The route handler
     * @param callable[] $middleware Optional middleware for this route
     */
    public static function delete(string $path, callable $handler, array $middleware = []): void
    {
        self::addRoute('DELETE', $path, $handler, $middleware);
    }

    /**
     * Register a PATCH route
     * 
     * @param string $path The route path
     * @param callable $handler The route handler
     * @param callable[] $middleware Optional middleware for this route
     */
    public static function patch(string $path, callable $handler, array $middleware = []): void
    {
        self::addRoute('PATCH', $path, $handler, $middleware);
    }

    /**
     * Register a route for any HTTP method
     * 
     * @param string $path The route path
     * @param callable $handler The route handler
     * @param callable[] $middleware Optional middleware for this route
     */
    public static function any(string $path, callable $handler, array $middleware = []): void
    {
        self::addRoute('ANY', $path, $handler, $middleware);
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
     * @param callable $middleware The middleware function
     */
    public static function middleware(callable $middleware): void
    {
        self::$middleware[] = $middleware;
    }

    /**
     * Add a route to the registry
     * 
     * @param string $method HTTP method
     * @param string $path Route path
     * @param callable $handler Route handler
     * @param callable[] $middleware Route-specific middleware
     */
    protected static function addRoute(string $method, string $path, callable $handler, array $middleware = []): void
    {
        self::$routes[] = [
            'method' => strtoupper($method),
            'path' => self::normalizePath($path),
            'handler' => $handler,
            'middleware' => $middleware
        ];
    }

    /**
     * Resolve a handler definition which may be a callable or a string like
     * "role/module/Controller@method" or "Controller@method".
     *
     * @param callable|string $handler
     * @return callable
     */
    private static function resolveHandler($handler): callable
    {
        if (is_callable($handler)) {
            return $handler;
        }

        if (is_string($handler)) {
            // Supported formats:
            // - "admin/Blog/form@index" (role/module/controller@method)
            // - "BlogFormController@index" (fqcn or simple class in module is out of scope here)
            // - "Controller@method"

            $parts = explode('@', $handler, 2);
            $classPart = $parts[0] ?? '';
            $method = $parts[1] ?? 'index';

            $role = null;
            $module = null;
            $controller = null;

            if (str_contains($classPart, '/')) {
                // role/module/name
                $segments = explode('/', $classPart);
                [$role, $module, $controller] = array_pad($segments, 3, null);
            } else {
                $controller = $classPart;
            }

            $callable = function () use ($role, $module, $controller, $method) {
                // Minimal controller resolver compatible with Controller base class
                if (!class_exists('Controller')) {
                    throw new RuntimeException('Base Controller class not loaded');
                }

                // Build guesses for controller file path when role/module provided
                $instance = null;
                $loadedFile = null;
                if ($role && $module) {
                    $base = defined('DIR_BODY') ? DIR_BODY : (defined('BODY_DIR') ? BODY_DIR : ROOT . DIRECTORY_SEPARATOR . 'Body');
                    // Try different file name patterns
                    $fileVariations = [
                        $controller, // HeaderController
                        strtolower($controller), // headercontroller
                        lcfirst($controller), // headerController
                        strtolower(preg_replace('/Controller$/', '', $controller)), // header
                    ];
                    
                    $paths = [];
                    foreach ($fileVariations as $variation) {
                        $paths[] = $base . DIRECTORY_SEPARATOR . $role . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . $variation . '.php';
                        $paths[] = $base . DIRECTORY_SEPARATOR . $role . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . $variation . '.php';
                    }
                    foreach ($paths as $p) {
                        if (is_readable($p)) {
                            try {
                                require_once $p;
                                $loadedFile = realpath($p) ?: $p;
                                break;
                            } catch (Throwable $e) {
                                error_log("Failed to load controller file $p: " . $e->getMessage());
                                continue;
                            }
                        } else {
                        }
                    }
                }

                // Try common controller class naming conventions
                $candidates = [];
                if ($controller) {
                    $candidates[] = ucfirst($controller) . 'Controller';
                    $candidates[] = $controller; // already capitalized name
                    $candidates[] = ucfirst($role) . ucfirst($controller) . 'Controller';
                }

                $registry = $GLOBALS['registry'] ?? (class_exists('Registry') ? Registry::getInstance() : null);
                foreach ($candidates as $fqcn) {
                    if (class_exists($fqcn)) {
                        try {
                            $instance = new $fqcn($registry);
                            break;
                        } catch (Throwable $e) {
                            error_log("Failed to instantiate controller $fqcn: " . $e->getMessage());
                            continue;
                        }
                    }
                }

                if ($instance === null && $loadedFile) {
                    // Fallback: find any class in the included file that extends Controller
                    foreach (get_declared_classes() as $decl) {
                        try {
                            $rc = new ReflectionClass($decl);
                        } catch (ReflectionException $e) {
                            continue;
                        }
                        $fileName = $rc->getFileName();
                        if ($fileName && realpath($fileName) === realpath($loadedFile)) {
                            if ($rc->isInstantiable() && $rc->isSubclassOf('Controller')) {
                                $registry = $GLOBALS['registry'] ?? (class_exists('Registry') ? Registry::getInstance() : null);
                                try {
                                    $instance = $rc->newInstance($registry);
                                    break;
                                } catch (Throwable $_) {
                                    // continue searching
                                }
                            }
                        }
                    }
                }

                if ($instance === null) {
                    throw new RuntimeException('Controller not found for handler: ' . (string)$controller);
                }

                if (!method_exists($instance, $method)) {
                    throw new RuntimeException('Method ' . $method . ' not found in controller ' . get_class($instance));
                }

                try {
                    $instance->{$method}();
                } catch (Throwable $e) {
                    error_log("Controller method error in " . get_class($instance) . "::{$method}: " . $e->getMessage());
                    throw $e;
                }
            };

            return $callable;
        }

        throw new InvalidArgumentException('Invalid route handler definition');
    }

    /**
     * Normalize route path
     * 
     * @param string $path The route path
     * @return string Normalized path
     */
    private static function normalizePath(string $path): string
    {
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
        // Exact match
        if ($routePath === $requestPath) {
            return [];
        }

        // Check for dynamic parameters like {slug}
        if (str_contains($routePath, '{')) {
            $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $routePath);
            $pattern = '#^' . $pattern . '$#';
            
            if (preg_match($pattern, $requestPath, $matches)) {
                array_shift($matches); // Remove full match
                
                // Extract parameter names
                preg_match_all('/\{([^}]+)\}/', $routePath, $paramNames);
                $paramNames = $paramNames[1];
                
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
     */
    public static function run(): void
    {
        $uri = self::getCurrentUri();
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // Find matching route
        $route = self::findRoute($method, $uri);
        
        if ($route) {
            self::executeRoute($route);
        } else {
            self::handleNotFound();
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
        foreach (self::$routes as $route) {
            if (self::matchesRoute($route, $method, $uri)) {
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
     * @return bool True if route matches
     */
    private static function matchesRoute(array $route, string $method, string $uri): bool
    {
        // Check HTTP method
        if ($route['method'] !== 'ANY' && $route['method'] !== $method) {
            return false;
        }

        // Check path with dynamic parameter support
        $params = self::matchRoute($route['path'], $uri);
        if ($params !== false) {
            // Store route parameters in $_GET for access in controllers
            foreach ($params as $key => $value) {
                $_GET[$key] = $value;
            }
            return true;
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
            // Execute global middleware
            foreach (self::$middleware as $middleware) {
                if (self::executeMiddleware($middleware) === false) {
                    return; // Middleware stopped execution
                }
            }

            // Execute route-specific middleware
            foreach ($route['middleware'] as $middleware) {
                if (self::executeMiddleware($middleware) === false) {
                    return; // Middleware stopped execution
                }
            }

            // Execute route handler
            call_user_func($route['handler']);

        } catch (Throwable $e) {
            self::handleError($e);
        }
    }

    /**
     * Execute middleware
     * 
     * @param callable $middleware Middleware function
     * @return bool|void True to continue, false to stop, void for no return
     */
    private static function executeMiddleware(callable $middleware)
    {
        return call_user_func($middleware);
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
        echo '<div style="margin: 2rem auto; max-width: 600px; padding: 2rem; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 0.5rem; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif;">';
        echo '<h2 style="color: #dc3545; margin-top: 0;">404 Not Found</h2>';
        echo '<p style="color: #6c757d; margin-bottom: 0;">The requested page could not be found.</p>';
        echo '</div>';
    }

    /**
     * Handle errors
     * 
     * @param Throwable $e The exception
     */
    private static function handleError(Throwable $e): void
    {
        http_response_code(500);
        error_log("Router error: " . $e->getMessage());
        
        if (defined('APP_ENV') && APP_ENV === 'development') {
            echo '<div style="margin: 2rem auto; max-width: 600px; padding: 2rem; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 0.5rem; font-family: monospace;">';
            echo '<h2 style="color: #dc3545; margin-top: 0;">500 Internal Server Error</h2>';
            echo '<p style="color: #6c757d; margin-bottom: 0;">' . htmlspecialchars($e->getMessage()) . '</p>';
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
     * Clear all routes
     */
    public static function clearRoutes(): void
    {
        self::$routes = [];
    }

    /**
     * Load routes from JSON file
     * 
     * @param string $filePath Path to JSON routes file
     */
    public static function loadFromJson(string $filePath): void
    {
        if (!file_exists($filePath)) {
            return;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return;
        }

        $routes = json_decode($content, true);
        if (!is_array($routes)) {
            return;
        }

        foreach ($routes['routes'] ?? [] as $route) {
            if (isset($route['method'], $route['path'], $route['handler'])) {
                $method = strtolower($route['method']);
                if (method_exists(self::class, $method)) {
                    $handler = self::resolveHandler($route['handler']);
                    self::{$method}($route['path'], $handler);
                }
            }
        }
    }
}
