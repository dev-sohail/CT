<?php
/**
 * Class Str
 *
 * Utility class for common string operations.
 */
class Str
{
    /**
     * Convert a string to lowercase.
     */
    public static function lower(string $value): string
    {
        return mb_strtolower($value, 'UTF-8');
    }

    /**
     * Convert a string to uppercase.
     */
    public static function upper(string $value): string
    {
        return mb_strtoupper($value, 'UTF-8');
    }

    /**
     * Capitalize the first letter of a string.
     */
    public static function ucfirst(string $value): string
    {
        return mb_strtoupper(mb_substr($value, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($value, 1, null, 'UTF-8');
    }

    /**
     * Limit the length of a string and append a suffix if truncated.
     */
    public static function limit(string $value, int $limit = 100, string $suffix = '...'): string
    {
        if (mb_strlen($value, 'UTF-8') <= $limit) {
            return $value;
        }
        return mb_substr($value, 0, $limit, 'UTF-8') . $suffix;
    }

    /**
     * Check if a string contains a given substring.
     */
    public static function contains(string $haystack, string $needle): bool
    {
        return mb_strpos($haystack, $needle, 0, 'UTF-8') !== false;
    }

    /**
     * Replace a part of the string.
     */
    public static function replace(string $search, string $replace, string $subject): string
    {
        return str_replace($search, $replace, $subject);
    }
}
