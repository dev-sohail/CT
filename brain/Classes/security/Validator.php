<?php
/**
 * Class Validator
 *
 * Provides common validation methods for strings, numbers, emails, URLs, and more.
 */
class Validator
{
    /**
     * Validate that a value is not empty.
     */
    public function required($value): bool
    {
        return !empty($value) || $value === '0';
    }

    /**
     * Validate an email address.
     */
    public function email(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate a URL.
     */
    public function url(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate that a string is within a certain length range.
     */
    public function length(string $value, int $min, int $max): bool
    {
        $len = strlen($value);
        return $len >= $min && $len <= $max;
    }

    /**
     * Validate that a value is numeric and optionally within a range.
     */
    public function number($value, ?float $min = null, ?float $max = null): bool
    {
        if (!is_numeric($value)) {
            return false;
        }
        if ($min !== null && $value < $min) {
            return false;
        }
        if ($max !== null && $value > $max) {
            return false;
        }
        return true;
    }

    /**
     * Validate against a regular expression.
     */
    public function regex(string $value, string $pattern): bool
    {
        return preg_match($pattern, $value) === 1;
    }
}
