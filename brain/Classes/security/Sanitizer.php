<?php
/**
 * Class Sanitizer
 *
 * Provides various sanitization utilities for strings, numbers, and arrays.
 */
class Sanitizer
{
    /**
     * Sanitize a string by removing HTML tags and trimming whitespace.
     */
    public function sanitizeString(?string $value): string
    {
        return trim(strip_tags((string)$value));
    }

    /**
     * Sanitize an integer value.
     */
    public function sanitizeInt($value): int
    {
        return (int)filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Sanitize a float value.
     */
    public function sanitizeFloat($value): float
    {
        $clean = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND);
        return (float)$clean;
    }

    /**
     * Sanitize an email address.
     */
    public function sanitizeEmail(?string $email): string
    {
        return filter_var((string)$email, FILTER_SANITIZE_EMAIL);
    }

    /**
     * Sanitize a URL.
     */
    public function sanitizeUrl(?string $url): string
    {
        return filter_var((string)$url, FILTER_SANITIZE_URL);
    }

    /**
     * Sanitize an array recursively.
     */
    public function sanitizeArray(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                $sanitized[$key] = $this->sanitizeString($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }
}
