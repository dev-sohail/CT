<?php
/**
 * Class InputNormalizer
 *
 * Provides methods to sanitize and normalize various types of user input.
 */
class InputNormalizer
{
    /**
     * Normalize a string by trimming whitespace and optionally lowercasing.
     */
    public function normalizeString(?string $value, bool $toLower = false): string
    {
        $value = trim((string)$value);
        return $toLower ? mb_strtolower($value, 'UTF-8') : $value;
    }

    /**
     * Normalize an integer input.
     */
    public function normalizeInt($value): int
    {
        return (int)filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE) ?? 0;
    }

    /**
     * Normalize a float input.
     */
    public function normalizeFloat($value): float
    {
        return (float)filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE) ?? 0.0;
    }

    /**
     * Normalize and validate an email address.
     */
    public function normalizeEmail(?string $email): string
    {
        $email = filter_var(trim((string)$email), FILTER_SANITIZE_EMAIL);
        return filter_var($email, FILTER_VALIDATE_EMAIL) ?: '';
    }

    /**
     * Normalize and validate a URL.
     */
    public function normalizeUrl(?string $url): string
    {
        $url = trim((string)$url);
        return filter_var($url, FILTER_VALIDATE_URL) ?: '';
    }

    /**
     * Sanitize HTML to prevent XSS.
     */
    public function sanitizeHtml(?string $html): string
    {
        return htmlspecialchars((string)$html, ENT_QUOTES, 'UTF-8');
    }
}
