<?php
/**
 * Class MakeController
 *
 * Provides functionality to create controller files with a basic template.
 */
class MakeController
{
    protected string $controllerPath;

    public function __construct(string $controllerPath)
    {
        $this->controllerPath = rtrim($controllerPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * Create a new controller file.
     */
    public function create(string $name, bool $overwrite = false): bool
    {
        $filename = $this->controllerPath . $name . '.php';

        if (file_exists($filename) && !$overwrite) {
            return false;
        }

        $template = <<<PHP
<?php
class {$name}
{
    public function index()
    {
        echo "{$name} controller index method.";
    }
}
PHP;

        if (!is_dir($this->controllerPath)) {
            mkdir($this->controllerPath, 0777, true);
        }

        return file_put_contents($filename, $template) !== false;
    }
}
