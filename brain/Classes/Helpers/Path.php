<?php

declare(strict_types=1);

/**
 * Class Path
 *
 * Comprehensive utility class for working with file system paths across platforms.
 */
class Path
{
    /**
     * Join multiple path segments into a single path.
     */
    public static function join(string ...$segments): string
    {
        if (empty($segments)) {
            return '';
        }

        $path = implode(DIRECTORY_SEPARATOR, $segments);
        return self::normalize($path);
    }

    /**
     * Normalize a path (resolve . and .., fix slashes).
     */
    public static function normalize(string $path): string
    {
        $isAbsolute = self::isAbsolute($path);
        $isWindows = DIRECTORY_SEPARATOR === '\\';

        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), fn($part) => $part !== '' && $part !== '.');

        $stack = [];
        foreach ($parts as $part) {
            if ($part === '..') {
                if (!empty($stack) && end($stack) !== '..') {
                    array_pop($stack);
                } elseif (!$isAbsolute) {
                    $stack[] = $part;
                }
            } else {
                $stack[] = $part;
            }
        }

        $normalized = implode(DIRECTORY_SEPARATOR, $stack);

        if ($isAbsolute) {
            if ($isWindows && preg_match('/^[A-Z]:/i', $path)) {
                // Keep drive letter for Windows
                $normalized = strtoupper(substr($path, 0, 2)) . DIRECTORY_SEPARATOR . $normalized;
            } else {
                $normalized = DIRECTORY_SEPARATOR . $normalized;
            }
        }

        return $normalized ?: '.';
    }

    /**
     * Get the directory name from a path.
     */
    public static function dirname(string $path, int $levels = 1): string
    {
        return dirname($path, $levels);
    }

    /**
     * Get the base name (file name with extension) from a path.
     */
    public static function basename(string $path, string $suffix = ''): string
    {
        return basename($path, $suffix);
    }

    /**
     * Get the file extension from a path.
     */
    public static function extension(string $path): string
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Get the filename without extension.
     */
    public static function filename(string $path): string
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * Check if a path is absolute.
     */
    public static function isAbsolute(string $path): bool
    {
        if (empty($path)) {
            return false;
        }

        // Unix-style absolute path
        if ($path[0] === '/') {
            return true;
        }

        // Windows-style absolute path
        if (DIRECTORY_SEPARATOR === '\\' && preg_match('/^[A-Z]:\\\\/i', $path)) {
            return true;
        }

        // UNC path
        if (str_starts_with($path, '\\\\')) {
            return true;
        }

        return false;
    }

    /**
     * Convert to absolute path.
     */
    public static function resolve(string ...$paths): string
    {
        $resolved = getcwd() ?: '';

        foreach ($paths as $path) {
            if (self::isAbsolute($path)) {
                $resolved = $path;
            } else {
                $resolved = self::join($resolved, $path);
            }
        }

        return self::normalize($resolved);
    }

    /**
     * Get relative path from one directory to another.
     */
    public static function relative(string $from, string $to): string
    {
        $from = self::normalize($from);
        $to = self::normalize($to);

        $fromParts = explode(DIRECTORY_SEPARATOR, trim($from, DIRECTORY_SEPARATOR));
        $toParts = explode(DIRECTORY_SEPARATOR, trim($to, DIRECTORY_SEPARATOR));

        $common = 0;
        $length = min(count($fromParts), count($toParts));

        for ($i = 0; $i < $length; $i++) {
            if ($fromParts[$i] === $toParts[$i]) {
                $common++;
            } else {
                break;
            }
        }

        $up = array_fill(0, count($fromParts) - $common, '..');
        $down = array_slice($toParts, $common);

        return implode(DIRECTORY_SEPARATOR, array_merge($up, $down)) ?: '.';
    }

    /**
     * Check if path exists.
     */
    public static function exists(string $path): bool
    {
        return file_exists($path);
    }

    /**
     * Check if path is a directory.
     */
    public static function isDirectory(string $path): bool
    {
        return is_dir($path);
    }

    /**
     * Check if path is a file.
     */
    public static function isFile(string $path): bool
    {
        return is_file($path);
    }
}
