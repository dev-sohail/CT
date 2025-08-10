<?php
/**
 * Class Slugger
 *
 * Generates URL-friendly slugs from strings.
 */
class Slugger
{
    /**
     * Convert a string into a slug.
     */
    public function slugify(string $text, string $separator = '-'): string
    {
        // Convert to lowercase
        $text = strtolower($text);

        // Replace non-letter or digits with separator
        $text = preg_replace('~[^\pL\d]+~u', $separator, $text);

        // Transliterate
        $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);

        // Remove unwanted characters
        $text = preg_replace('~[^-a-z0-9]+~', '', $text);

        // Trim
        $text = trim($text, $separator);

        // Remove duplicate separators
        $text = preg_replace('~-+~', $separator, $text);

        return $text ?: 'n-a';
    }
}
