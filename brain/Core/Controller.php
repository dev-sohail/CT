<?php

declare(strict_types=1);

/**
 * Base Controller Class
 * 
 * Provides common functionality for all controllers in the framework
 */
abstract class Controller
{
    protected object $registry;
    protected array $data = [];

    public function __construct(object $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Load a model from a module
     * 
     * @param string $role The role (admin, public, etc.)
     * @param string $module The module name
     * @param string $name The model name
     * @return object The loaded model instance
     * @throws RuntimeException If model cannot be loaded
     */
    protected function loadModel(string $role, string $module, string $name): object
    {
        $basePath = $this->getModuleBasePath($role, $module);
        $candidates = [
            $basePath . '/Models/' . $name . '.php',
            $basePath . '/models/' . $name . '.php',
        ];

        foreach ($candidates as $candidate) {
            if (is_readable($candidate)) {
                return $this->loadModelFromFile($candidate, $name);
            }
        }

        throw new RuntimeException("Model not found: {$name} in module {$role}/{$module}");
    }

    /**
     * Load model from specific file
     */
    private function loadModelFromFile(string $filePath, string $name): object
    {
        require_once $filePath;
        
        $className = ucfirst($name) . 'Model';
        if (class_exists($className)) {
            return new $className($this->registry);
        }

        // Fallback to just the name
        $className = ucfirst($name);
        if (class_exists($className)) {
            return new $className($this->registry);
        }

        throw new RuntimeException("Model class not found: {$className} in file {$filePath}");
    }

    /**
     * Get module base path
     */
    private function getModuleBasePath(string $role, string $module): string
    {
        if (defined('DIR_BODY')) {
            return DIR_BODY . DIRECTORY_SEPARATOR . $role . DIRECTORY_SEPARATOR . $module;
        }
        
        if (defined('DIR_MODULES')) {
            return DIR_MODULES . DIRECTORY_SEPARATOR . $role . DIRECTORY_SEPARATOR . $module;
        }

        return ROOT . DIRECTORY_SEPARATOR . 'Body' . DIRECTORY_SEPARATOR . $role . DIRECTORY_SEPARATOR . $module;
    }

    /**
     * Load a view from a module
     * 
     * @param string $role The role (admin, public, etc.)
     * @param string $module The module name
     * @param string $name The view name
     * @param array $data Data to pass to the view
     * @throws RuntimeException If view cannot be loaded
     */
    protected function loadView(string $role, string $module, string $name, array $data = []): void
    {
        $basePath = $this->getModuleBasePath($role, $module);
        $candidates = [
            $basePath . '/Views/' . $name . '.ct',
            $basePath . '/Views/' . $name . '.php',
            $basePath . '/views/' . $name . '.ct',
            $basePath . '/views/' . $name . '.php',
        ];

        foreach ($candidates as $candidate) {
            if (is_readable($candidate)) {
                try {
                    $this->renderView($candidate, $data);
                    return;
                } catch (Throwable $e) {
                    error_log("Failed to render view $candidate: " . $e->getMessage());
                    continue;
                }
            }
        }

        // Fallback: create a simple error view
        $this->renderFallbackView($name, $data);
    }

    /**
     * Render view with data
     */
    private function renderView(string $viewPath, array $data): void
    {
        // Extract data for view usage
        extract($data, EXTR_SKIP);
        
        // Start output buffering
        ob_start();
        
        try {
            include $viewPath;
            echo ob_get_clean();
        } catch (Throwable $e) {
            ob_end_clean();
            throw new RuntimeException("Error rendering view {$viewPath}: " . $e->getMessage());
        }
    }

    /**
     * Render fallback view when original view is not found
     */
    private function renderFallbackView(string $name, array $data): void
    {
        $title = $data['title'] ?? 'View Not Found';
        echo "<div style='padding: 20px; font-family: Arial, sans-serif;'>";
        echo "<h1>" . htmlspecialchars($title) . "</h1>";
        echo "<p>View file '{$name}' not found. This is a fallback view.</p>";
        echo "<p>Please create the view file or check the path.</p>";
        echo "</div>";
    }

    /**
     * Load another controller inside a module
     * 
     * @param string $role The role (admin, public, etc.)
     * @param string $module The module name
     * @param string $name The controller name
     * @return object The loaded controller instance
     * @throws RuntimeException If controller cannot be loaded
     */
    protected function loadController(string $role, string $module, string $name): object
    {
        $basePath = $this->getModuleBasePath($role, $module);
        $candidates = [
            $basePath . '/Controllers/' . $name . '.php',
            $basePath . '/controllers/' . $name . '.php',
        ];

        foreach ($candidates as $candidate) {
            if (is_readable($candidate)) {
                return $this->loadControllerFromFile($candidate, $name);
            }
        }

        throw new RuntimeException("Controller not found: {$name} in module {$role}/{$module}");
    }

    /**
     * Load controller from specific file
     */
    private function loadControllerFromFile(string $filePath, string $name): object
    {
        require_once $filePath;
        
        $className = ucfirst($name) . 'Controller';
        if (class_exists($className)) {
            return new $className($this->registry);
        }

        // Fallback to just the name
        $className = ucfirst($name);
        if (class_exists($className)) {
            return new $className($this->registry);
        }

        throw new RuntimeException("Controller class not found: {$className} in file {$filePath}");
    }

    /**
     * Load module-specific routes
     * 
     * @param string $role The role (admin, public, etc.)
     * @param string $module The module name
     */
    protected function loadRoutes(string $role, string $module): void
    {
        $basePath = $this->getModuleBasePath($role, $module);
        $candidates = [
            $basePath . '/routes.json',
            $basePath . '/routes.php',
            $basePath . '/Routes.php',
        ];

        foreach ($candidates as $path) {
            if (is_readable($path)) {
                if (str_ends_with($path, '.json')) {
                    $this->loadJsonRoutes($path);
                } else {
                    require_once $path;
                }
                break;
            }
        }
    }

    /**
     * Load JSON routes configuration
     */
    private function loadJsonRoutes(string $path): void
    {
        $content = file_get_contents($path);
        if ($content === false) {
            return;
        }

        $routes = json_decode($content, true);
        if (!is_array($routes)) {
            return;
        }

        // Process JSON routes - this would integrate with the routing system
        foreach ($routes['routes'] ?? [] as $route) {
            // Route processing logic would go here
            error_log("Loaded route: " . json_encode($route));
        }
    }

    /**
     * Set a variable for view usage
     * 
     * @param string $key The variable key
     * @param mixed $value The variable value
     */
    protected function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Get a variable from controller data
     * 
     * @param string $key The variable key
     * @param mixed $default Default value if key not found
     * @return mixed The variable value
     */
    protected function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Render and pass all data to a view
     * 
     * @param string $role The role (admin, public, etc.)
     * @param string $module The module name
     * @param string $view The view name
     */
    protected function render(string $role, string $module, string $view): void
    {
        $this->loadView($role, $module, $view, $this->data);
    }

    /**
     * Redirect to a URL
     * 
     * @param string $url The URL to redirect to
     * @param int $statusCode HTTP status code for redirect
     */
    protected function redirect(string $url, int $statusCode = 302): void
    {
        http_response_code($statusCode);
        header("Location: {$url}");
        exit;
    }

    /**
     * Return JSON response
     * 
     * @param mixed $data The data to encode as JSON
     * @param int $statusCode HTTP status code
     */
    protected function jsonResponse(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}
