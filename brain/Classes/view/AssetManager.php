<?php
/**
 * Class AssetManager
 *
 * Manages asset registration, retrieval, and versioning for scripts, styles, or other resources.
 */
class AssetManager
{
    protected array $assets = [];

    /**
     * Register a new asset.
     */
    public function register(string $name, string $path, string $type = 'script', ?string $version = null): void
    {
        $this->assets[$name] = [
            'path'    => $path,
            'type'    => $type,
            'version' => $version ?? md5_file($path)
        ];
    }

    /**
     * Get asset details by name.
     */
    public function get(string $name): ?array
    {
        return $this->assets[$name] ?? null;
    }

    /**
     * Remove an asset by name.
     */
    public function remove(string $name): void
    {
        unset($this->assets[$name]);
    }

    /**
     * List all registered assets.
     */
    public function all(): array
    {
        return $this->assets;
    }

    /**
     * Generate HTML tag for an asset.
     */
    public function render(string $name): ?string
    {
        if (!isset($this->assets[$name])) {
            return null;
        }

        $asset = $this->assets[$name];
        $pathWithVersion = $asset['path'] . '?v=' . $asset['version'];

        if ($asset['type'] === 'script') {
            return "<script src=\"{$pathWithVersion}\"></script>";
        } elseif ($asset['type'] === 'style') {
            return "<link rel=\"stylesheet\" href=\"{$pathWithVersion}\">";
        }

        return null;
    }
}
