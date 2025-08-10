<?php
/**
 * Class Backup
 *
 * Handles creation, storage, and restoration of application backups.
 */
class Backup
{
    protected string $backupPath;

    public function __construct(string $backupPath)
    {
        $this->backupPath = rtrim($backupPath, '/');
    }

    /**
     * Create a backup of the given directory.
     */
    public function create(string $sourceDir, string $backupName): bool
    {
        $destination = $this->backupPath . '/' . $backupName . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        $sourceDir = realpath($sourceDir);
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            if (is_dir($file)) {
                continue;
            }
            $filePath = realpath($file);
            $relativePath = substr($filePath, strlen($sourceDir) + 1);
            $zip->addFile($filePath, $relativePath);
        }

        return $zip->close();
    }

    /**
     * Restore a backup to the given directory.
     */
    public function restore(string $backupName, string $targetDir): bool
    {
        $backupFile = $this->backupPath . '/' . $backupName . '.zip';
        if (!file_exists($backupFile)) {
            return false;
        }

        $zip = new ZipArchive();
        if ($zip->open($backupFile) !== true) {
            return false;
        }

        $result = $zip->extractTo($targetDir);
        $zip->close();

        return $result;
    }

    /**
     * List all available backups.
     */
    public function listBackups(): array
    {
        return glob($this->backupPath . '/*.zip') ?: [];
    }
}
