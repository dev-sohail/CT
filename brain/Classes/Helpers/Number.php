<?php
/**
 * Class Number
 *
 * Utility class for number formatting and calculations.
 */
class Number
{
    /**
     * Format a number with grouped thousands.
     */
    public static function format(float $number, int $decimals = 0, string $decimalPoint = '.', string $thousandsSeparator = ','): string
    {
        return number_format($number, $decimals, $decimalPoint, $thousandsSeparator);
    }

    /**
     * Round a number to a given precision.
     */
    public static function round(float $number, int $precision = 0): float
    {
        return round($number, $precision);
    }

    /**
     * Generate a random number within a range.
     */
    public static function random(int $min, int $max): int
    {
        return random_int($min, $max);
    }

    /**
     * Check if a number is even.
     */
    public static function isEven(int $number): bool
    {
        return $number % 2 === 0;
    }

    /**
     * Check if a number is odd.
     */
    public static function isOdd(int $number): bool
    {
        return $number % 2 !== 0;
    }
}
