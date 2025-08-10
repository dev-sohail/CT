<?php

abstract class Controller
{
    protected $registry;
    protected $data = [];

    public function __construct($registry)
    {
        $this->registry = $registry;
    }

    /**
     * Load a model from a module
     */
    protected function loadModel($role, $module, $name)
    {
        $base = rtrim(DIR_MODULES, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $role . DIRECTORY_SEPARATOR . $module;
        $candidates = [
            $base . '/Models/' . $name . '.php',
            $base . '/models/' . $name . '.php',
        ];
        $path = null;
        foreach ($candidates as $candidate) {
            if (is_readable($candidate)) { $path = $candidate; break; }
        }

        if ($path) {
            require_once($path);
            $class = ucfirst($name);
            if (class_exists($class)) {
                return new $class($this->registry);
            }
        }

        throw new Exception("Model not found: $path");
    }

    /**
     * Load a view from a module
     */
    protected function loadView($role, $module, $name, $data = [])
    {
        $base = rtrim(DIR_MODULES, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $role . DIRECTORY_SEPARATOR . $module;
        $candidates = [
            $base . '/Views/' . $name . '.php',
            $base . '/Views/' . $name . '.ct.php',
            $base . '/view/' . $name . '.php',
            $base . '/view/' . $name . '.ct.php',
        ];
        $path = null;
        foreach ($candidates as $candidate) {
            if (is_readable($candidate)) { $path = $candidate; break; }
        }

        if ($path) {
            extract($data);
            require($path);
        } else {
            throw new Exception("View not found: $path");
        }
    }

    /**
     * Load another controller inside a module (optional)
     */
    protected function loadController($role, $module, $name)
    {
        $base = rtrim(DIR_MODULES, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $role . DIRECTORY_SEPARATOR . $module;
        $candidates = [
            $base . '/Controllers/' . $name . '.php',
            $base . '/controllers/' . $name . '.php',
        ];
        $path = null;
        foreach ($candidates as $candidate) {
            if (is_readable($candidate)) { $path = $candidate; break; }
        }

        if ($path) {
            require_once($path);
            $class = ucfirst($name);
            if (class_exists($class)) {
                return new $class($this->registry);
            }
        }

        throw new Exception("Controller not found: $path");
    }

    /**
     * Load module-specific routes manually (if needed)
     */
    protected function loadRoutes($role, $module)
    {
        $base = rtrim(DIR_MODULES, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $role . DIRECTORY_SEPARATOR . $module;
        foreach ([$base . '/routes.php', $base . '/Routes.php'] as $path) {
            if (is_readable($path)) {
                require_once($path);
                break;
            }
        }
    }

    /**
     * Set a variable for view usage
     */
    protected function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Render and pass all data to a view
     */
    protected function render($role, $module, $view)
    {
        $this->loadView($role, $module, $view, $this->data);
    }
}
