<?php

declare(strict_types=1);

/**
 * Class Headers
 *
 * Comprehensive utility class for managing HTTP headers with security features.
 */
class Headers
{
    /**
     * Set a header.
     */
    public static function set(string $name, string $value, bool $replace = true): void
    {
        if (!headers_sent()) {
            header($name . ': ' . $value, $replace);
        }
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
        if (!headers_sent()) {
            header_remove($name);
        }
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

    /**
     * Set security headers.
     */
    public static function security(array $options = []): void
    {
        $defaults = [
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
        ];

        $headers = array_merge($defaults, $options);

        foreach ($headers as $name => $value) {
            if ($value !== null) {
                self::set($name, $value);
            }
        }
    }

    /**
     * Set Content Security Policy header.
     */
    public static function csp(array $directives): void
    {
        $policy = [];
        foreach ($directives as $directive => $sources) {
            if (is_array($sources)) {
                $policy[] = $directive . ' ' . implode(' ', $sources);
            } else {
                $policy[] = $directive . ' ' . $sources;
            }
        }

        self::set('Content-Security-Policy', implode('; ', $policy));
    }

    /**
     * Set HSTS (HTTP Strict Transport Security) header.
     */
    public static function hsts(int $maxAge = 31536000, bool $includeSubDomains = true, bool $preload = false): void
    {
        $value = "max-age={$maxAge}";
        
        if ($includeSubDomains) {
            $value .= '; includeSubDomains';
        }

        if ($preload) {
            $value .= '; preload';
        }

        self::set('Strict-Transport-Security', $value);
    }

    /**
     * Set cache control headers.
     */
    public static function cache(string $type = 'no-cache', int $maxAge = 0): void
    {
        switch ($type) {
            case 'public':
                self::set('Cache-Control', "public, max-age={$maxAge}");
                break;
            case 'private':
                self::set('Cache-Control', "private, max-age={$maxAge}");
                break;
            case 'no-cache':
                self::set('Cache-Control', 'no-cache, no-store, must-revalidate');
                self::set('Pragma', 'no-cache');
                self::set('Expires', '0');
                break;
        }
    }

    /**
     * Set content type header.
     */
    public static function contentType(string $type, string $charset = 'utf-8'): void
    {
        self::set('Content-Type', "{$type}; charset={$charset}");
    }

    /**
     * Check if headers have been sent.
     */
    public static function sent(): bool
    {
        return headers_sent();
    }

    /**
     * Get response code.
     */
    public static function getResponseCode(): int
    {
        return http_response_code();
    }

    /**
     * Set response code.
     */
    public static function setResponseCode(int $code): void
    {
        http_response_code($code);
    }
}
