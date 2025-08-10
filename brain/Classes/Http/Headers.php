<?php
/**
 * Class Headers
 *
 * Utility class for managing HTTP headers.
 */
class Headers
{
    /**
     * Set a header.
     */
    public static function set(string $name, string $value, bool $replace = true): void
    {
        header($name . ': ' . $value, $replace);
    }

    /**
     * Get a header from the request.
     */
    public static function get(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$key] ?? null;
    }

    /**
     * Check if a header exists in the request.
     */
    public static function has(string $name): bool
    {
        return self::get($name) !== null;
    }

    /**
     * Remove a header from the response.
     */
    public static function remove(string $name): void
    {
        header_remove($name);
    }

    /**
     * Get all request headers.
     */
    public static function all(): array
    {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) === 'HTTP_') {
                $headerName = str_replace('_', '-', substr($key, 5));
                $headers[$headerName] = $value;
            }
        }
        return $headers;
    }
}
