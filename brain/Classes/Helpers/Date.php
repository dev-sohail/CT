<?php

declare(strict_types=1);

class Date
{
    public static function now(string $format = 'Y-m-d H:i:s'): string
    {
        return date($format);
    }

    public static function format(int|string $timestamp, string $format = 'Y-m-d H:i:s'): string
    {
        $time = is_string($timestamp) ? strtotime($timestamp) : $timestamp;
        return date($format, $time);
    }

    public static function parse(string $dateString): int
    {
        return strtotime($dateString) ?: time();
    }

    public static function create(int $year, int $month, int $day, int $hour = 0, int $minute = 0, int $second = 0): int
    {
        return mktime($hour, $minute, $second, $month, $day, $year);
    }

    public static function addDays(string|int $date, int $days, string $format = 'Y-m-d H:i:s'): string
    {
        $timestamp = is_string($date) ? strtotime($date) : $date;
        return date($format, strtotime("+{$days} days", $timestamp));
    }

    public static function subDays(string|int $date, int $days, string $format = 'Y-m-d H:i:s'): string
    {
        $timestamp = is_string($date) ? strtotime($date) : $date;
        return date($format, strtotime("-{$days} days", $timestamp));
    }

    public static function addMonths(string|int $date, int $months, string $format = 'Y-m-d H:i:s'): string
    {
        $timestamp = is_string($date) ? strtotime($date) : $date;
        return date($format, strtotime("+{$months} months", $timestamp));
    }

    public static function addYears(string|int $date, int $years, string $format = 'Y-m-d H:i:s'): string
    {
        $timestamp = is_string($date) ? strtotime($date) : $date;
        return date($format, strtotime("+{$years} years", $timestamp));
    }

    public static function diff(string|int $date1, string|int $date2): int
    {
        $time1 = is_string($date1) ? strtotime($date1) : $date1;
        $time2 = is_string($date2) ? strtotime($date2) : $date2;
        return abs($time1 - $time2);
    }

    public static function diffInDays(string|int $date1, string|int $date2): int
    {
        return (int)floor(self::diff($date1, $date2) / 86400);
    }

    public static function diffInHours(string|int $date1, string|int $date2): int
    {
        return (int)floor(self::diff($date1, $date2) / 3600);
    }

    public static function isWeekend(string|int $date): bool
    {
        $timestamp = is_string($date) ? strtotime($date) : $date;
        $day = (int)date('N', $timestamp);
        return $day >= 6;
    }

    public static function isToday(string|int $date): bool
    {
        $timestamp = is_string($date) ? strtotime($date) : $date;
        return date('Y-m-d', $timestamp) === date('Y-m-d');
    }

    public static function isPast(string|int $date): bool
    {
        $timestamp = is_string($date) ? strtotime($date) : $date;
        return $timestamp < time();
    }

    public static function isFuture(string|int $date): bool
    {
        $timestamp = is_string($date) ? strtotime($date) : $date;
        return $timestamp > time();
    }

    public static function ago(string|int $date): string
    {
        $timestamp = is_string($date) ? strtotime($date) : $date;
        $diff = time() - $timestamp;

        if ($diff < 60) return $diff . ' seconds ago';
        if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
        if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
        if ($diff < 604800) return floor($diff / 86400) . ' days ago';
        if ($diff < 2592000) return floor($diff / 604800) . ' weeks ago';
        if ($diff < 31536000) return floor($diff / 2592000) . ' months ago';
        
        return floor($diff / 31536000) . ' years ago';
    }

    public static function startOfDay(string|int $date): int
    {
        $timestamp = is_string($date) ? strtotime($date) : $date;
        return strtotime('today', $timestamp);
    }

    public static function endOfDay(string|int $date): int
    {
        $timestamp = is_string($date) ? strtotime($date) : $date;
        return strtotime('tomorrow', $timestamp) - 1;
    }
}
