<?php
/**
 * Class Cookie
 *
 * Utility class for setting, getting, and deleting cookies.
 */
class Cookie
{
    /**
     * Set a cookie.
     */
    public static function set(string $name, string $value, int $expire = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httpOnly = true): bool
    {
        return setcookie($name, $value, [
            'expires'  => $expire > 0 ? time() + $expire : 0,
            'path'     => $path,
            'domain'   => $domain,
            'secure'   => $secure,
            'httponly' => $httpOnly,
            'samesite' => 'Lax'
        ]);
    }

    /**
     * Get a cookie value.
     */
    public static function get(string $name, $default = null)
    {
        return $_COOKIE[$name] ?? $default;
    }

    /**
     * Check if a cookie exists.
     */
    public static function has(string $name): bool
    {
        return isset($_COOKIE[$name]);
    }

    /**
     * Delete a cookie.
     */
    public static function delete(string $name, string $path = '/', string $domain = '', bool $secure = false, bool $httpOnly = true): bool
    {
        if (self::has($name)) {
            setcookie($name, '', [
                'expires'  => time() - 3600,
                'path'     => $path,
                'domain'   => $domain,
                'secure'   => $secure,
                'httponly' => $httpOnly,
                'samesite' => 'Lax'
            ]);
            unset($_COOKIE[$name]);
            return true;
        }
        return false;
    }
}
