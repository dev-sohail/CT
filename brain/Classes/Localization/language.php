<?php
/**
 * Class Language
 *
 * A simple language manager for loading and retrieving translations.
 */
class Language
{
    protected array $translations = [];
    protected string $locale = 'en';

    public function __construct(string $locale = 'en')
    {
        $this->locale = $locale;
    }

    /**
     * Set the current locale.
     */
    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    /**
     * Load translations from an array or file.
     */
    public function load(array|string $source): void
    {
        if (is_array($source)) {
            $this->translations = array_merge($this->translations, $source);
        } elseif (is_file($source)) {
            $data = include $source;
            if (is_array($data)) {
                $this->translations = array_merge($this->translations, $data);
            }
        }
    }

    /**
     * Get a translation by key, with optional replacements.
     */
    public function get(string $key, array $replace = []): string
    {
        $translation = $this->translations[$this->locale][$key] ?? $key;

        foreach ($replace as $search => $value) {
            $translation = str_replace(':' . $search, $value, $translation);
        }

        return $translation;
    }
}
