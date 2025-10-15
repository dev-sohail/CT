<?php

declare(strict_types=1);

class Number
{
    public static function format(float $number, int $decimals = 0, string $decPoint = '.', string $thousandsSep = ','): string
    {
        return number_format($number, $decimals, $decPoint, $thousandsSep);
    }

    public static function currency(float $amount, string $currency = '$', int $decimals = 2): string
    {
        return $currency . self::format($amount, $decimals);
    }

    public static function percentage(float $number, int $decimals = 2): string
    {
        return self::format($number, $decimals) . '%';
    }

    public static function round(float $number, int $precision = 0, int $mode = PHP_ROUND_HALF_UP): float
    {
        return round($number, $precision, $mode);
    }

    public static function floor(float $number): int
    {
        return (int)floor($number);
    }

    public static function ceil(float $number): int
    {
        return (int)ceil($number);
    }

    public static function random(int $min = 0, int $max = 100): int
    {
        return random_int($min, $max);
    }

    public static function isEven(int $number): bool
    {
        return $number % 2 === 0;
    }

    public static function isOdd(int $number): bool
    {
        return $number % 2 !== 0;
    }

    public static function clamp(float $number, float $min, float $max): float
    {
        return max($min, min($max, $number));
    }

    public static function between(float $number, float $min, float $max): bool
    {
        return $number >= $min && $number <= $max;
    }

    public static function abs(float $number): float
    {
        return abs($number);
    }

    public static function max(float ...$numbers): float
    {
        return max($numbers);
    }

    public static function min(float ...$numbers): float
    {
        return min($numbers);
    }

    public static function average(array $numbers): float
    {
        return count($numbers) > 0 ? array_sum($numbers) / count($numbers) : 0;
    }

    public static function sum(array $numbers): float
    {
        return array_sum($numbers);
    }

    public static function ordinal(int $number): string
    {
        $ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
        
        if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
            return $number . 'th';
        }
        
        return $number . $ends[$number % 10];
    }

    public static function fileSize(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    public static function abbreviate(float $number, int $precision = 1): string
    {
        $units = ['', 'K', 'M', 'B', 'T'];
        
        for ($i = 0; $number >= 1000 && $i < count($units) - 1; $i++) {
            $number /= 1000;
        }
        
        return round($number, $precision) . $units[$i];
    }

    public static function toRoman(int $number): string
    {
        $map = [
            'M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400,
            'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40,
            'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1
        ];
        
        $result = '';
        foreach ($map as $roman => $value) {
            $matches = (int)($number / $value);
            $result .= str_repeat($roman, $matches);
            $number %= $value;
        }
        
        return $result;
    }
}
