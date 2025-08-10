<?php
/**
 * Class Locale
 *
 * Handles locale-specific settings such as date formats, number formats, and language preferences.
 */
class Local
{
    protected string $locale;
    protected array $settings = [];

    public function __construct(string $locale = 'en_US', array $settings = [])
    {
        $this->locale = $locale;
        $this->settings = $settings;
    }

    /**
     * Set the current locale.
     */
    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    /**
     * Get the current locale.
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * Set a locale-specific setting.
     */
    public function set(string $key, mixed $value): void
    {
        $this->settings[$key] = $value;
    }

    /**
     * Get a locale-specific setting.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    /**
     * Format a date according to locale settings.
     */
    public function formatDate(int|string $timestamp, string $format = null): string
    {
        $format = $format ?? ($this->settings['date_format'] ?? 'Y-m-d');
        $time = is_numeric($timestamp) ? (int)$timestamp : strtotime($timestamp);
        return date($format, $time);
    }

    /**
     * Format a number according to locale settings.
     */
    public function formatNumber(float $number, int $decimals = 2): string
    {
        $decimalPoint = $this->settings['decimal_point'] ?? '.';
        $thousandsSep = $this->settings['thousands_sep'] ?? ',';
        return number_format($number, $decimals, $decimalPoint, $thousandsSep);
    }
}
