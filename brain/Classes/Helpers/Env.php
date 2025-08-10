<?php
/**
 * Class Env
 *
 * Utility class for managing environment variables.
 */
class Env
{
    /**
     * Get an environment variable, with optional default value.
     */
    public static function get(string $key, $default = null)
    {
        $value = getenv($key);
        if ($value === false) {
            return $default;
        }

        // Handle boolean and null conversions
        $lower = strtolower($value);
        if ($lower === 'true') return true;
        if ($lower === 'false') return false;
        if ($lower === 'null') return null;

        return $value;
    }

    /**
     * Set an environment variable.
     */
    public static function set(string $key, $value): void
    {
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    /**
     * Load environment variables from a .env file.
     */
    public static function load(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            [$name, $value] = array_map('trim', explode('=', $line, 2));
            self::set($name, $value);
        }
    }
}
