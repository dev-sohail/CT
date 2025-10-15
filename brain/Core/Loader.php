<?php

declare(strict_types=1);

/**
 * Loader Class
 * 
 * CodeIgniter/OpenCart-style loader for models, libraries, helpers, and views
 * Provides easy access to framework components
 * 
 * @version 2.0.0
 */
class Loader
{
    protected object $registry;
    protected array $loadedModels = [];
    protected array $loadedLibraries = [];
    protected array $loadedHelpers = [];
    protected array $vars = [];

    public function __construct(object $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Load a model (OpenCart/CodeIgniter style)
     * 
     * @param string $model Model path (e.g., 'admin/blog/Blog' or 'BlogModel')
     * @param string|null $name Optional alias name
     * @param bool $database Whether to initialize database
     * @return object The loaded model instance
     */
    public function model(string $model, ?string $name = null, bool $database = true): object
    {
        // Parse model path
        $parts = explode('/', $model);
        
        if (count($parts) === 3) {
            // Format: role/module/ModelName
            [$role, $module, $modelName] = $parts;
        } elseif (count($parts) === 1) {
            // Format: ModelName (assume current context)
            $modelName = $parts[0];
            $role = null;
            $module = null;
        } else {
            throw new RuntimeException("Invalid model path format: $model");
        }

        // Generate cache key
        $cacheKey = $role ? "$role/$module/$modelName" : $modelName;
        
        // Return if already loaded
        if (isset($this->loadedModels[$cacheKey])) {
            return $this->loadedModels[$cacheKey];
        }

        // Determine alias name
        $alias = $name ?? $this->getModelAlias($modelName);

        // Load the model
        $modelInstance = $this->loadModelFile($role, $module, $modelName);

        // Cache the model
        $this->loadedModels[$cacheKey] = $modelInstance;

        // Register in registry with alias
        $this->registry->set($alias, $modelInstance);

        return $modelInstance;
    }

    /**
     * Load a library/class from Brain/Classes
     * 
     * @param string $library Library path (e.g., 'database/Database' or 'Cache')
     * @param array $params Constructor parameters
     * @param string|null $name Optional alias name
     * @return object The loaded library instance
     */
    public function library(string $library, array $params = [], ?string $name = null): object
    {
        // Check if already loaded
        if (isset($this->loadedLibraries[$library])) {
            return $this->loadedLibraries[$library];
        }

        // Build file path
        $libraryPath = $this->getLibraryPath($library);
        
        if (!file_exists($libraryPath)) {
            throw new RuntimeException("Library not found: $library at $libraryPath");
        }

        require_once $libraryPath;

        // Get class name from library path
        $className = $this->getLibraryClassName($library);

        if (!class_exists($className)) {
            throw new RuntimeException("Library class not found: $className");
        }

        // Instantiate the library
        $instance = empty($params) ? new $className($this->registry) : new $className(...$params);

        // Cache the library
        $this->loadedLibraries[$library] = $instance;

        // Register in registry
        $alias = $name ?? strtolower(basename($library));
        $this->registry->set($alias, $instance);

        return $instance;
    }

    /**
     * Load a helper file
     * 
     * @param string|array $helpers Helper name(s)
     * @return void
     */
    public function helper($helpers): void
    {
        $helpers = is_array($helpers) ? $helpers : [$helpers];

        foreach ($helpers as $helper) {
            if (isset($this->loadedHelpers[$helper])) {
                continue;
            }

            $helperPath = $this->getHelperPath($helper);

            if (!file_exists($helperPath)) {
                error_log("Helper not found: $helper at $helperPath");
                continue;
            }

            require_once $helperPath;
            $this->loadedHelpers[$helper] = true;
        }
    }

    /**
     * Load a view file
     * 
     * @param string $view View path (e.g., 'admin/blog/form' or 'header')
     * @param array $data Data to pass to view
     * @param bool $return Whether to return output instead of displaying
     * @return string|void View output if $return is true
     */
    public function view(string $view, array $data = [], bool $return = false)
    {
        // Merge with stored vars
        $data = array_merge($this->vars, $data);

        // Parse view path
        $parts = explode('/', $view);
        
        if (count($parts) === 3) {
            // Format: role/module/viewName
            [$role, $module, $viewName] = $parts;
        } else {
            throw new RuntimeException("Invalid view path format: $view. Expected 'role/module/viewName'");
        }

        // Build view file path
        $viewPath = $this->getViewPath($role, $module, $viewName);

        if (!file_exists($viewPath)) {
            throw new RuntimeException("View not found: $view at $viewPath");
        }

        // Extract data for view
        extract($data, EXTR_SKIP);

        // Start output buffering
        ob_start();

        try {
            include $viewPath;
            $output = ob_get_clean();

            if ($return) {
                return $output;
            }

            echo $output;
        } catch (Throwable $e) {
            ob_end_clean();
            throw new RuntimeException("Error rendering view $view: " . $e->getMessage());
        }
    }

    /**
     * Set a variable for views
     * 
     * @param string|array $key Variable name or array of variables
     * @param mixed $value Variable value
     * @return void
     */
    public function vars($key, $value = null): void
    {
        if (is_array($key)) {
            $this->vars = array_merge($this->vars, $key);
        } else {
            $this->vars[$key] = $value;
        }
    }

    /**
     * Load model file
     * 
     * @param string|null $role Role name
     * @param string|null $module Module name
     * @param string $modelName Model name
     * @return object Model instance
     */
    private function loadModelFile(?string $role, ?string $module, string $modelName): object
    {
        if ($role && $module) {
            $basePath = $this->getModuleBasePath($role, $module);
            
            // Try multiple filename variations
            $fileVariations = [
                $modelName . '.php',                    // About.php
                $modelName . 'Model.php',               // AboutModel.php
                ucfirst($modelName) . 'Model.php',      // AboutModel.php
                strtolower($modelName) . '.php',        // about.php
                strtolower($modelName) . '_model.php',  // about_model.php
            ];
            
            $modelFile = null;
            foreach ($fileVariations as $filename) {
                // Try Models/ directory (uppercase)
                $testFile = $basePath . '/Models/' . $filename;
                if (file_exists($testFile)) {
                    $modelFile = $testFile;
                    break;
                }
                
                // Try models/ directory (lowercase)
                $testFile = $basePath . '/models/' . $filename;
                if (file_exists($testFile)) {
                    $modelFile = $testFile;
                    break;
                }
            }
        } else {
            // Try to find in current context or default location
            throw new RuntimeException("Model loading without role/module context not yet implemented");
        }

        if (!$modelFile || !file_exists($modelFile)) {
            throw new RuntimeException("Model file not found: $modelName in $role/$module");
        }

        require_once $modelFile;

        // Try different class name patterns
        $classNames = [
            $modelName,
            ucfirst($modelName),
            $modelName . 'Model',
            ucfirst($modelName) . 'Model'
        ];

        foreach ($classNames as $className) {
            if (class_exists($className)) {
                return new $className($this->registry);
            }
        }

        throw new RuntimeException("Model class not found for: $modelName (tried: " . implode(', ', $classNames) . ")");
    }

    /**
     * Get library file path
     * 
     * @param string $library Library name
     * @return string Library file path
     */
    private function getLibraryPath(string $library): string
    {
        $base = defined('DIR_BRAIN_CLASSES') ? DIR_BRAIN_CLASSES : (defined('ROOT') ? ROOT . '/Brain/Classes' : '');
        return $base . '/' . $library . '.php';
    }

    /**
     * Get library class name
     * 
     * @param string $library Library path
     * @return string Class name
     */
    private function getLibraryClassName(string $library): string
    {
        return ucfirst(basename($library));
    }

    /**
     * Get helper file path
     * 
     * @param string $helper Helper name
     * @return string Helper file path
     */
    private function getHelperPath(string $helper): string
    {
        $base = defined('DIR_BRAIN_CLASSES') ? DIR_BRAIN_CLASSES : (defined('ROOT') ? ROOT . '/Brain/Classes' : '');
        return $base . '/Helpers/' . $helper . '.php';
    }

    /**
     * Get view file path
     * 
     * @param string $role Role name
     * @param string $module Module name
     * @param string $viewName View name
     * @return string View file path
     */
    private function getViewPath(string $role, string $module, string $viewName): string
    {
        $basePath = $this->getModuleBasePath($role, $module);
        
        // Try .ct extension first, then .php
        $viewFile = $basePath . '/Views/' . $viewName . '.ct';
        if (file_exists($viewFile)) {
            return $viewFile;
        }

        $viewFile = $basePath . '/Views/' . $viewName . '.php';
        if (file_exists($viewFile)) {
            return $viewFile;
        }

        // Try lowercase views directory
        $viewFile = $basePath . '/views/' . $viewName . '.ct';
        if (file_exists($viewFile)) {
            return $viewFile;
        }

        return $basePath . '/views/' . $viewName . '.php';
    }

    /**
     * Get module base path
     * 
     * @param string $role Role name
     * @param string $module Module name
     * @return string Module base path
     */
    private function getModuleBasePath(string $role, string $module): string
    {
        if (defined('DIR_BODY')) {
            return DIR_BODY . '/' . $role . '/' . $module;
        }

        if (defined('BODY_DIR')) {
            return BODY_DIR . '/' . $role . '/' . $module;
        }

        if (defined('ROOT')) {
            return ROOT . '/Body/' . $role . '/' . $module;
        }

        throw new RuntimeException("Body directory not defined");
    }

    /**
     * Get model alias name
     * 
     * @param string $modelName Model name
     * @return string Alias name
     */
    private function getModelAlias(string $modelName): string
    {
        // Remove 'Model' suffix if present
        $alias = preg_replace('/Model$/', '', $modelName);
        return 'model_' . strtolower($alias);
    }

    /**
     * Get all loaded models
     * 
     * @return array Loaded models
     */
    public function getLoadedModels(): array
    {
        return $this->loadedModels;
    }

    /**
     * Get all loaded libraries
     * 
     * @return array Loaded libraries
     */
    public function getLoadedLibraries(): array
    {
        return $this->loadedLibraries;
    }

    /**
     * Get all loaded helpers
     * 
     * @return array Loaded helpers
     */
    public function getLoadedHelpers(): array
    {
        return array_keys($this->loadedHelpers);
    }
}

