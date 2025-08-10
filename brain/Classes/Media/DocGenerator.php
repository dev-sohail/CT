<?php
/**
 * Class DocGenerator
 *
 * Generates documentation from code comments, annotations, or structured data.
 */
class DocGenerator
{
    protected string $sourcePath;
    protected string $outputPath;

    public function __construct(string $sourcePath, string $outputPath)
    {
        $this->sourcePath = rtrim($sourcePath, '/');
        $this->outputPath = rtrim($outputPath, '/');
    }

    /**
     * Generate documentation for all PHP files in the source path.
     */
    public function generate(): void
    {
        $files = glob($this->sourcePath . '/*.php');

        foreach ($files as $file) {
            $doc = $this->extractDocsFromFile($file);
            $this->saveDoc(basename($file, '.php') . '.md', $doc);
        }
    }

    /**
     * Extract documentation comments from a file.
     */
    protected function extractDocsFromFile(string $file): string
    {
        $content = file_get_contents($file);
        preg_match_all('/\/\*\*(.*?)\*\//s', $content, $matches);
        return implode("\n\n", array_map('trim', $matches[1]));
    }

    /**
     * Save the generated documentation to the output path.
     */
    protected function saveDoc(string $filename, string $content): void
    {
        if (!is_dir($this->outputPath)) {
            mkdir($this->outputPath, 0777, true);
        }
        file_put_contents($this->outputPath . '/' . $filename, $content);
    }
}
