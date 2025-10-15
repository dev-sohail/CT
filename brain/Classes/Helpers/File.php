<?php

declare(strict_types=1);

class File
{
    public static function exists(string $path): bool
    {
        return file_exists($path);
    }

    public static function missing(string $path): bool
    {
        return !self::exists($path);
    }

    public static function get(string $path): ?string
    {
        return self::exists($path) ? file_get_contents($path) : null;
    }

    public static function put(string $path, string $contents, bool $lock = false): int|false
    {
        return file_put_contents($path, $contents, $lock ? LOCK_EX : 0);
    }

    public static function append(string $path, string $data): int|false
    {
        return file_put_contents($path, $data, FILE_APPEND | LOCK_EX);
    }

    public static function prepend(string $path, string $data): int|false
    {
        if (self::exists($path)) {
            return self::put($path, $data . self::get($path));
        }

        return self::put($path, $data);
    }

    public static function delete(string|array $paths): bool
    {
        $paths = is_array($paths) ? $paths : func_get_args();
        $success = true;

        foreach ($paths as $path) {
            if (!@unlink($path)) {
                $success = false;
            }
        }

        return $success;
    }

    public static function move(string $path, string $target): bool
    {
        return rename($path, $target);
    }

    public static function copy(string $path, string $target): bool
    {
        return copy($path, $target);
    }

    public static function name(string $path): string
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    public static function basename(string $path): string
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }

    public static function dirname(string $path): string
    {
        return pathinfo($path, PATHINFO_DIRNAME);
    }

    public static function extension(string $path): string
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    public static function type(string $path): string|false
    {
        return filetype($path);
    }

    public static function mimeType(string $path): string|false
    {
        return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
    }

    public static function size(string $path): int
    {
        return filesize($path);
    }

    public static function lastModified(string $path): int
    {
        return filemtime($path);
    }

    public static function isDirectory(string $path): bool
    {
        return is_dir($path);
    }

    public static function isFile(string $path): bool
    {
        return is_file($path);
    }

    public static function isReadable(string $path): bool
    {
        return is_readable($path);
    }

    public static function isWritable(string $path): bool
    {
        return is_writable($path);
    }

    public static function makeDirectory(string $path, int $mode = 0755, bool $recursive = false): bool
    {
        return mkdir($path, $mode, $recursive);
    }

    public static function deleteDirectory(string $directory): bool
    {
        if (!self::isDirectory($directory)) {
            return false;
        }

        $items = new \FilesystemIterator($directory);

        foreach ($items as $item) {
            if ($item->isDir() && !$item->isLink()) {
                self::deleteDirectory($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }

        return @rmdir($directory);
    }

    public static function files(string $directory): array
    {
        $glob = glob($directory . '/*');
        return $glob === false ? [] : array_filter($glob, 'is_file');
    }

    public static function allFiles(string $directory): array
    {
        $result = [];
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($items as $item) {
            if ($item->isFile()) {
                $result[] = $item->getPathname();
            }
        }

        return $result;
    }
}
