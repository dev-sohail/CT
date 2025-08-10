<?php
/**
 * Class Url
 *
 * Utility class for handling and manipulating URLs.
 */
class Url
{
    /**
     * Build a URL from a base and query parameters.
     */
    public static function build(string $base, array $params = []): string
    {
        if (empty($params)) {
            return $base;
        }
        return $base . '?' . http_build_query($params);
    }

    /**
     * Get a specific component from a URL.
     */
    public static function getPart(string $url, int $component): ?string
    {
        $part = parse_url($url, $component);
        return $part !== false ? $part : null;
    }

    /**
     * Add or update a query parameter in a URL.
     */
    public static function withQueryParam(string $url, string $key, string $value): string
    {
        $parts = parse_url($url);
        parse_str($parts['query'] ?? '', $query);
        $query[$key] = $value;
        $parts['query'] = http_build_query($query);

        return self::unparseUrl($parts);
    }

    /**
     * Remove a query parameter from a URL.
     */
    public static function withoutQueryParam(string $url, string $key): string
    {
        $parts = parse_url($url);
        parse_str($parts['query'] ?? '', $query);
        unset($query[$key]);
        $parts['query'] = http_build_query($query);

        return self::unparseUrl($parts);
    }

    /**
     * Rebuild URL from parsed parts.
     */
    protected static function unparseUrl(array $parts): string
    {
        return ($parts['scheme'] ?? '') . '://' . ($parts['host'] ?? '')
            . (isset($parts['port']) ? ":{$parts['port']}" : '')
            . ($parts['path'] ?? '')
            . (!empty($parts['query']) ? "?{$parts['query']}" : '')
            . (!empty($parts['fragment']) ? "#{$parts['fragment']}" : '');
    }
}
