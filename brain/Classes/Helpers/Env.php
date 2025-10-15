<?php

declare(strict_types=1);

/**
 * Class Env
 *
 * Comprehensive utility class for managing environment variables with type casting.
 */
class Env
{
    protected static array $cache = [];

    /**
     * Get an environment variable with type casting.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        self::$cache[$key] = self::cast($value);
        return self::$cache[$key];
    }

    /**
     * Get string value.
     */
    public static function getString(string $key, string $default = ''): string
    {
        $value = self::get($key, $default);
        return is_string($value) ? $value : (string)$value;
    }

    /**
     * Get integer value.
     */
    public static function getInt(string $key, int $default = 0): int
    {
        $value = self::get($key, $default);
        return is_int($value) ? $value : (int)$value;
    }

    /**
     * Get boolean value.
     */
    public static function getBool(string $key, bool $default = false): bool
    {
        $value = self::get($key, $default);
        return is_bool($value) ? $value : filter_var($value, FILTER_VALIDATE_BOOL);
    }

    /**
     * Get float value.
     */
    public static function getFloat(string $key, float $default = 0.0): float
    {
        $value = self::get($key, $default);
        return is_float($value) ? $value : (float)$value;
    }

    /**
     * Get array value (comma-separated).
     */
    public static function getArray(string $key, array $default = []): array
    {
        $value = self::get($key);

        if ($value === null) {
            return $default;
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            return array_map('trim', explode(',', $value));
        }

        return $default;
    }

    /**
     * Set an environment variable.
     */
    public static function set(string $key, mixed $value): void
    {
        $strValue = is_bool($value) ? ($value ? 'true' : 'false') : (string)$value;
        
        putenv("{$key}={$strValue}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        self::$cache[$key] = $value;
    }

    /**
     * Check if an environment variable exists.
     */
    public static function has(string $key): bool
    {
        return isset($_ENV[$key]) || isset($_SERVER[$key]) || getenv($key) !== false;
    }

    /**
     * Load environment variables from a .env file.
     */
    public static function load(string $path): bool
    {
        if (!file_exists($path)) {
            return false;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return false;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = array_map('trim', explode('=', $line, 2));

            // Remove quotes
            $value = trim($value, '"\'');

            self::set($name, $value);
        }

        return true;
    }

    /**
     * Cast string value to appropriate type.
     */
    protected static function cast(string $value): mixed
    {
        $lower = strtolower($value);

        // Boolean values
        if ($lower === 'true' || $lower === '(true)') {
            return true;
        }
        if ($lower === 'false' || $lower === '(false)') {
            return false;
        }

        // Null value
        if ($lower === 'null' || $lower === '(null)') {
            return null;
        }

        // Empty value
        if ($lower === 'empty' || $lower === '(empty)') {
            return '';
        }

        // Numeric values
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float)$value : (int)$value;
        }

        return $value;
    }

    /**
     * Clear the cache.
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }

    /**
     * Get all environment variables.
     */
    public static function all(): array
    {
        return array_merge($_ENV, $_SERVER);
    }
}
