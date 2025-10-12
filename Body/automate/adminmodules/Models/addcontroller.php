<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Model.php';

/**
 * Add Controller Model
 * 
 * Handles controller creation logic
 */
class AddControllerModel extends Model
{
    protected string $table = 'generated_controllers';

    /**
     * Generate controller code
     */
    public function generateController(string $name, string $module, string $role): string
    {
        $className = ucfirst($name) . 'Controller';
        
        return "<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Controller.php';

/**
 * {$className}
 * 
 * Generated controller for {$module} module
 */
class {$className} extends Controller
{
    public function index(): void
    {
        \$data = [
            'title' => '{$name}',
            'module' => '{$module}'
        ];

        \$this->loadView('{$role}', '{$module}', '{$name}', \$data);
    }
}";
    }
}