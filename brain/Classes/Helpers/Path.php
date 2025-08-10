<?php
/**
 * Class Path
 *
 * Utility class for working with file system paths.
 */
class Path
{
    /**
     * Join multiple path segments into a single path.
     */
    public static function join(string ...$segments): string
    {
        return preg_replace('#/+#', '/', join('/', $segments));
    }

    /**
     * Get the directory name from a path.
     */
    public static function dirname(string $path): string
    {
        return dirname($path);
    }

    /**
     * Get the base name (file name with extension) from a path.
     */
    public static function basename(string $path): string
    {
        return basename($path);
    }

    /**
     * Get the file extension from a path.
     */
    public static function extension(string $path): string
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Check if a path is absolute.
     */
    public static function isAbsolute(string $path): bool
    {
        return ($path[0] === '/' || preg_match('/^[A-Z]:\\\\/i', $path) === 1);
    }
}
