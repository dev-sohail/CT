<?php
/**
 * Class File
 *
 * Utility class for file handling operations.
 */
class File
{
    /**
     * Check if a file exists.
     */
    public static function exists(string $path): bool
    {
        return file_exists($path);
    }

    /**
     * Read file contents.
     */
    public static function read(string $path): ?string
    {
        return self::exists($path) ? file_get_contents($path) : null;
    }

    /**
     * Write contents to a file.
     */
    public static function write(string $path, string $content): bool
    {
        return file_put_contents($path, $content) !== false;
    }

    /**
     * Append contents to a file.
     */
    public static function append(string $path, string $content): bool
    {
        return file_put_contents($path, $content, FILE_APPEND) !== false;
    }

    /**
     * Delete a file.
     */
    public static function delete(string $path): bool
    {
        return self::exists($path) ? unlink($path) : false;
    }

    /**
     * Get file size in bytes.
     */
    public static function size(string $path): ?int
    {
        return self::exists($path) ? filesize($path) : null;
    }

    /**
     * Get the file's last modified time.
     */
    public static function lastModified(string $path): ?int
    {
        return self::exists($path) ? filemtime($path) : null;
    }
}
