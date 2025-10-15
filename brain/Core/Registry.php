<?php

declare(strict_types=1);

/**
 * Registry Class
 * 
 * OpenCart/CodeIgniter-style dependency injection container
 * Provides centralized access to framework services and components
 * 
 * @version 2.0.0
 */
final class Registry
{
    /**
     * Internal storage for registered items
     *
     * @var array<string, mixed>
     */
    private array $data = [];

    /**
     * Lazy-loaded services
     *
     * @var array<string, callable>
     */
    private array $factories = [];

    /**
     * Singleton instance
     */
    private static ?self $instance = null;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {
        $this->registerCoreServices();
    }

    /**
     * Get singleton instance
     * 
     * @return self The registry instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register core framework services
     */
    private function registerCoreServices(): void
    {
        // Register loader service (lazy loaded)
        $this->factories['load'] = function() {
            $loaderPath = __DIR__ . '/Loader.php';
            if (!class_exists('Loader', false) && file_exists($loaderPath)) {
                require_once $loaderPath;
            }
            if (class_exists('Loader', false)) {
                return new Loader($this);
            }
            throw new RuntimeException('Loader class not found');
        };

        // Register router service (already initialized)
        if (class_exists('Router', false)) {
            $this->data['router'] = 'Router';
        }
    }

    /**
     * Retrieve an item by key (OpenCart/CodeIgniter style)
     * Supports lazy loading of services
     *
     * @param string $key The key to fetch
     * @return mixed|null Returns stored value or null if key not found
     */
    public function get(string $key): mixed
    {
        // Check if already loaded
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }

        // Check if factory exists
        if (isset($this->factories[$key])) {
            $this->data[$key] = call_user_func($this->factories[$key]);
            return $this->data[$key];
        }

        return null;
    }

    /**
     * Magic getter for OpenCart-style access
     * Allows $registry->db instead of $registry->get('db')
     *
     * @param string $key The key to fetch
     * @return mixed The value or null
     */
    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    /**
     * Magic setter for OpenCart-style access
     *
     * @param string $key The key
     * @param mixed $value The value
     */
    public function __set(string $key, mixed $value): void
    {
        $this->set($key, $value);
    }

    /**
     * Magic isset for OpenCart-style access
     *
     * @param string $key The key to check
     * @return bool True if exists
     */
    public function __isset(string $key): bool
    {
        return $this->has($key) || isset($this->factories[$key]);
    }

    /**
     * Store an item by key
     *
     * @param string $key   The key under which the value will be stored
     * @param mixed  $value The value to store
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Check if a key exists in the registry
     *
     * @param string $key The key to check
     * @return bool True if key exists, false otherwise
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Remove an item from the registry
     *
     * @param string $key The key to remove
     * @return bool True if item was removed, false if not found
     */
    public function remove(string $key): bool
    {
        if ($this->has($key)) {
            unset($this->data[$key]);
            return true;
        }
        return false;
    }

    /**
     * Get all registered keys
     *
     * @return array<string> Array of registered keys
     */
    public function keys(): array
    {
        return array_keys($this->data);
    }

    /**
     * Get all registered items
     *
     * @return array<string, mixed> Array of all registered items
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Clear all registered items
     *
     * @return void
     */
    public function clear(): void
    {
        $this->data = [];
    }

    /**
     * Register a service with a factory function
     *
     * @param string $key The service key
     * @param callable $factory The factory function
     * @return void
     */
    public function register(string $key, callable $factory): void
    {
        $this->data[$key] = $factory;
    }

    /**
     * Resolve a service (execute factory if it's a callable)
     *
     * @param string $key The service key
     * @return mixed The resolved service
     * @throws RuntimeException If service cannot be resolved
     */
    public function resolve(string $key): mixed
    {
        if (!$this->has($key)) {
            throw new RuntimeException("Service '{$key}' not found in registry");
        }

        $service = $this->data[$key];

        // If it's a callable (factory), execute it
        if (is_callable($service)) {
            $resolved = $service();
            $this->data[$key] = $resolved; // Cache the resolved service
            return $resolved;
        }

        return $service;
    }

    /**
     * Register a singleton service
     *
     * @param string $key The service key
     * @param callable $factory The factory function
     * @return void
     */
    public function singleton(string $key, callable $factory): void
    {
        $this->register($key, function() use ($factory) {
            static $instance = null;
            if ($instance === null) {
                $instance = $factory();
            }
            return $instance;
        });
    }

    /**
     * Get the count of registered items
     *
     * @return int The number of registered items
     */
    public function count(): int
    {
        return count($this->data);
    }

    /**
     * Check if registry is empty
     *
     * @return bool True if empty, false otherwise
     */
    public function isEmpty(): bool
    {
        return empty($this->data);
    }
}
