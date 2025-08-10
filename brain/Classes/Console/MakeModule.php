<?php
/**
 * Class MakeModule
 *
 * Provides functionality to create a module directory structure with basic files.
 */
class MakeModule
{
    protected string $modulePath;

    public function __construct(string $modulePath)
    {
        $this->modulePath = rtrim($modulePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * Create a new module with basic structure.
     */
    public function create(string $name, bool $overwrite = false): bool
    {
        $moduleDir = $this->modulePath . $name . DIRECTORY_SEPARATOR;

        if (is_dir($moduleDir) && !$overwrite) {
            return false;
        }

        // Create directories
        $dirs = ['Controllers', 'Models', 'Views'];
        foreach ($dirs as $dir) {
            if (!is_dir($moduleDir . $dir)) {
                mkdir($moduleDir . $dir, 0777, true);
            }
        }

        // Create placeholder files
        file_put_contents($moduleDir . 'module.php', "<?php\n// {$name} module bootstrap file\n");
        file_put_contents($moduleDir . 'Controllers/' . $name . 'Controller.php', "<?php\nclass {$name}Controller { }\n");
        file_put_contents($moduleDir . 'Models/' . $name . 'Model.php', "<?php\nclass {$name}Model { }\n");

        return true;
    }
}
