<?php
/**
 * Class AssetPipeline
 *
 * Handles the building, minification, and bundling of assets such as JavaScript and CSS.
 */
class AssetPipeline
{
    protected array $sources = [];
    protected string $outputDir;

    public function __construct(string $outputDir)
    {
        $this->outputDir = rtrim($outputDir, '/');
    }

    /**
     * Add an asset source file.
     */
    public function addSource(string $filePath, string $type): void
    {
        $this->sources[] = [
            'path' => $filePath,
            'type' => $type
        ];
    }

    /**
     * Process and bundle assets by type.
     */
    public function build(): void
    {
        $grouped = [];

        foreach ($this->sources as $source) {
            $grouped[$source['type']][] = $source['path'];
        }

        foreach ($grouped as $type => $files) {
            $content = '';
            foreach ($files as $file) {
                if (file_exists($file)) {
                    $content .= file_get_contents($file) . "\n";
                }
            }

            // Simple minification placeholder
            $minified = $this->minify($content, $type);

            $outputFile = $this->outputDir . "/bundle." . $type;
            file_put_contents($outputFile, $minified);
        }
    }

    /**
     * Very basic minification (remove comments and whitespace).
     */
    protected function minify(string $content, string $type): string
    {
        if ($type === 'js') {
            $content = preg_replace('/\/\*.*?\*\//s', '', $content); // Remove block comments
            $content = preg_replace('/\/\/.*$/m', '', $content); // Remove line comments
        } elseif ($type === 'css') {
            $content = preg_replace('/\/\*.*?\*\//s', '', $content);
        }

        return trim(preg_replace('/\s+/', ' ', $content));
    }
}
