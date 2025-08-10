<?php
/**
 * Class Date
 *
 * Utility class for handling date and time operations.
 */
class Date
{
    /**
     * Get the current date and time in a given format.
     */
    public static function now(string $format = 'Y-m-d H:i:s'): string
    {
        return date($format);
    }

    /**
     * Format a given timestamp.
     */
    public static function format(int $timestamp, string $format = 'Y-m-d H:i:s'): string
    {
        return date($format, $timestamp);
    }

    /**
     * Parse a date string into a timestamp.
     */
    public static function parse(string $dateString): int
    {
        return strtotime($dateString);
    }

    /**
     * Add days to a given date.
     */
    public static function addDays(string $dateString, int $days, string $format = 'Y-m-d H:i:s'): string
    {
        return date($format, strtotime("{$dateString} +{$days} days"));
    }

    /**
     * Subtract days from a given date.
     */
    public static function subDays(string $dateString, int $days, string $format = 'Y-m-d H:i:s'): string
    {
        return date($format, strtotime("{$dateString} -{$days} days"));
    }
}
