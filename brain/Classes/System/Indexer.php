<?php

class Indexer
{
    /**
     * Recursively index PHP files and basic symbols
     */
    public static function buildCodeIndex(string $rootDir, string $outputPath): bool
    {
        $index = [
            'files' => [],
            'classes' => [],
            'functions' => [],
        ];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootDir, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile()) continue;
            $path = $fileInfo->getRealPath();
            if (!$path || pathinfo($path, PATHINFO_EXTENSION) !== 'php') continue;

            $rel = str_replace($rootDir . DIRECTORY_SEPARATOR, '', $path);
            $index['files'][] = $rel;

            $content = @file_get_contents($path);
            if ($content === false) continue;

            // naive extraction
            if (preg_match_all('/class\s+([A-Za-z_][A-Za-z0-9_]*)/i', $content, $m)) {
                foreach ($m[1] as $className) {
                    $index['classes'][$className] = $rel;
                }
            }
            if (preg_match_all('/function\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(/i', $content, $m2)) {
                foreach ($m2[1] as $fn) {
                    $index['functions'][$fn][] = $rel;
                }
            }
        }

        @mkdir(dirname($outputPath), 0755, true);
        return file_put_contents($outputPath, json_encode($index, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== false;
    }
}


