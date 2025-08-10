<?php
/**
 * Class Arr
 *
 * Utility class for common array operations.
 */
class Arr
{
    /**
     * Get a value from a nested array using dot notation.
     */
    public static function get(array $array, string $key, $default = null)
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        $keys = explode('.', $key);
        foreach ($keys as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }

    /**
     * Set a value in a nested array using dot notation.
     */
    public static function set(array &$array, string $key, $value): void
    {
        $keys = explode('.', $key);
        while (count($keys) > 1) {
            $segment = array_shift($keys);
            if (!isset($array[$segment]) || !is_array($array[$segment])) {
                $array[$segment] = [];
            }
            $array = &$array[$segment];
        }
        $array[array_shift($keys)] = $value;
    }

    /**
     * Check if a given key exists in a nested array using dot notation.
     */
    public static function has(array $array, string $key): bool
    {
        $keys = explode('.', $key);
        foreach ($keys as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return false;
            }
            $array = $array[$segment];
        }
        return true;
    }
}
