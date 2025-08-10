<?php
/**
 * Class MakeModel
 *
 * Provides functionality to create model files with a basic template.
 */
class MakeModel
{
    protected string $modelPath;

    public function __construct(string $modelPath)
    {
        $this->modelPath = rtrim($modelPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * Create a new model file.
     */
    public function create(string $name, bool $overwrite = false): bool
    {
        $filename = $this->modelPath . $name . '.php';

        if (file_exists($filename) && !$overwrite) {
            return false;
        }

        $template = <<<PHP
<?php
class {$name}
{
    protected $table = '';

    public function __construct()
    {
        // Initialize model
    }
}
PHP;

        if (!is_dir($this->modelPath)) {
            mkdir($this->modelPath, 0777, true);
        }

        return file_put_contents($filename, $template) !== false;
    }
}
