<?php

declare(strict_types=1);

namespace Brain\Classes\Helpers;

use Brain\Core\Router;

class Url
{
    public static function base(string $path = ''): string
    {
        $base = defined('APP_ROOT_URL') ? rtrim(APP_ROOT_URL, '/') : '';
        return $base . '/' . ltrim($path, '/');
    }

    public static function asset(string $path = ''): string
    {
        $base = defined('APP_ASSETS_URL') ? rtrim(APP_ASSETS_URL, '/') : '/Storage';
        return $base . '/' . ltrim($path, '/');
    }

    public static function storage(string $path = ''): string
    {
        $base = defined('APP_STORAGE_URL') ? rtrim(APP_STORAGE_URL, '/') : '/Storage';
        return $base . '/' . ltrim($path, '/');
    }

    public static function admin(string $path = ''): string
    {
        $base = defined('APP_ADMIN_URL') ? rtrim(APP_ADMIN_URL, '/') : '/admin';
        return $base . '/' . ltrim($path, '/');
    }

    public static function api(string $path = ''): string
    {
        $base = defined('APP_API_URL') ? rtrim(APP_API_URL, '/') : '/api';
        return $base . '/' . ltrim($path, '/');
    }

    public static function current(): string
    {
        return defined('CURRENT_URL') ? CURRENT_URL : self::getCurrentUrl();
    }

    public static function currentUri(): string
    {
        return defined('CURRENT_URI') ? CURRENT_URI : ($_SERVER['REQUEST_URI'] ?? '/');
    }

    public static function route(string $name, array $params = [], array $query = []): string
    {
        if (class_exists('\Brain\Core\Router', false)) {
            return Router::url($name, $params, $query);
        }
        return self::base(implode('/', $params) . ($query ? '?' . http_build_query($query) : ''));
    }

    public static function isCurrent(string $path, bool $strict = false): bool
    {
        $current = parse_url(self::currentUri(), PHP_URL_PATH);
        $current = rtrim($current, '/');
        $path = rtrim($path, '/');
        return $strict ? ($current === $path) : str_starts_with($current, $path);
    }

    public static function redirect(string $url, int $code = 302): void
    {
        header('Location: ' . $url, true, $code);
        exit;
    }

    public static function protocol(): string
    {
        return defined('APP_PROTOCOL') ? APP_PROTOCOL : (self::isHttps() ? 'https://' : 'http://');
    }

    public static function host(): string
    {
        return defined('APP_HOST') ? APP_HOST : ($_SERVER['HTTP_HOST'] ?? 'localhost');
    }

    public static function basePath(): string
    {
        return defined('APP_BASE_PATH') ? APP_BASE_PATH : '/';
    }

    public static function isHttps(): bool
    {
        if (defined('APP_IS_HTTPS')) {
            return APP_IS_HTTPS;
        }
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
    }

    private static function getCurrentUrl(): string
    {
        $protocol = self::protocol();
        $host = self::host();
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return $protocol . $host . $uri;
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string {
        return \Brain\Classes\Helpers\Url::base($path);
    }
}

if (!function_exists('asset')) {
    function asset(string $path = ''): string {
        return \Brain\Classes\Helpers\Url::asset($path);
    }
}

if (!function_exists('admin_url')) {
    function admin_url(string $path = ''): string {
        return \Brain\Classes\Helpers\Url::admin($path);
    }
}

if (!function_exists('api_url')) {
    function api_url(string $path = ''): string {
        return \Brain\Classes\Helpers\Url::api($path);
    }
}

if (!function_exists('current_url')) {
    function current_url(): string {
        return \Brain\Classes\Helpers\Url::current();
    }
}

if (!function_exists('redirect_to')) {
    function redirect_to(string $url, int $code = 302): void {
        \Brain\Classes\Helpers\Url::redirect($url, $code);
    }
}
