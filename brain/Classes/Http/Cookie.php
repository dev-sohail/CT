<?php

declare(strict_types=1);

class Cookie
{
    protected static array $defaults = [
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ];

    public static function set(
        string $name, 
        string $value, 
        int $expire = 0, 
        ?string $path = null,
        ?string $domain = null,
        ?bool $secure = null,
        ?bool $httpOnly = null,
        ?string $sameSite = null
    ): bool {
        $options = [
            'expires' => $expire > 0 ? time() + $expire : 0,
            'path' => $path ?? self::$defaults['path'],
            'domain' => $domain ?? self::$defaults['domain'],
            'secure' => $secure ?? self::$defaults['secure'],
            'httponly' => $httpOnly ?? self::$defaults['httponly'],
            'samesite' => $sameSite ?? self::$defaults['samesite']
        ];

        return setcookie($name, $value, $options);
    }

    public static function get(string $name, mixed $default = null): mixed
    {
        return $_COOKIE[$name] ?? $default;
    }

    public static function has(string $name): bool
    {
        return isset($_COOKIE[$name]);
    }

    public static function delete(string $name, ?string $path = null, ?string $domain = null): bool
    {
        if (self::has($name)) {
            self::set($name, '', -3600, $path, $domain);
            unset($_COOKIE[$name]);
            return true;
        }
        return false;
    }

    public static function forever(string $name, string $value, ?string $path = null): bool
    {
        return self::set($name, $value, 315360000, $path);
    }

    public static function queue(string $name, string $value, int $expire = 0): void
    {
        $_COOKIE[$name] = $value;
    }

    public static function all(): array
    {
        return $_COOKIE;
    }

    public static function flush(): void
    {
        foreach (array_keys($_COOKIE) as $name) {
            self::delete($name);
        }
    }

    public static function setDefaults(array $defaults): void
    {
        self::$defaults = array_merge(self::$defaults, $defaults);
    }
}
