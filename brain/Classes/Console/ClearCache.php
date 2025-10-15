<?php

declare(strict_types=1);

/**
 * Class ClearCache
 *
 * Provides methods to clear application cache directories or specific files.
 */
class ClearCache
{
    /**
     * Clear all files in a given cache directory.
     */
    public function clearDirectory(string $directory): bool
    {
        if (!is_dir($directory)) {
            return false;
        }

        $files = scandir($directory);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $directory . DIRECTORY_SEPARATOR . $file;
            if (is_file($path)) {
                unlink($path);
            } elseif (is_dir($path)) {
                $this->clearDirectory($path);
                rmdir($path);
            }
        }
        return true;
    }

    /**
     * Clear a specific cache file.
     */
    public function clearFile(string $filePath): bool
    {
        if (is_file($filePath)) {
            return unlink($filePath);
        }
        return false;
    }

    /**
     * Clear multiple cache files at once.
     */
    public function clearFiles(array $filePaths): array
    {
        $results = [];
        foreach ($filePaths as $filePath) {
            $results[$filePath] = $this->clearFile($filePath);
        }
        return $results;
    }
}
