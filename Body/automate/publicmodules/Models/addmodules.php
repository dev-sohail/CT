<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Model.php';

/**
 * Add Modules Model
 * 
 * Handles module creation logic for public modules
 */
class AddModulesModel extends Model
{
    protected string $table = 'generated_modules';

    /**
     * Generate module structure
     */
    public function generateModule(string $name, string $role): array
    {
        return [
            'controllers' => [
                'index.php' => $this->generateIndexController($name, $role)
            ],
            'models' => [
                'index.php' => $this->generateIndexModel($name, $role)
            ],
            'views' => [
                'index.ct' => $this->generateIndexView($name, $role)
            ],
            'routes.json' => $this->generateRoutes($name, $role)
        ];
    }

    private function generateIndexController(string $name, string $role): string
    {
        $className = ucfirst($name) . 'Controller';
        
        return "<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Controller.php';

class {$className} extends Controller
{
    public function index(): void
    {
        \$data = ['title' => '{$name}'];
        \$this->loadView('{$role}', '{$name}', 'index', \$data);
    }
}";
    }

    private function generateIndexModel(string $name, string $role): string
    {
        $className = ucfirst($name) . 'Model';
        
        return "<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Model.php';

class {$className} extends Model
{
    protected string \$table = '{$name}';
}";
    }

    private function generateIndexView(string $name, string $role): string
    {
        return "<div>
    <h1><?= htmlspecialchars(\$title ?? '{$name}') ?></h1>
    <p>Welcome to the {$name} module!</p>
</div>";
    }

    private function generateRoutes(string $name, string $role): string
    {
        return json_encode([
            'routes' => [
                ['method' => 'GET', 'path' => "/{$role}/{$name}", 'handler' => "{$role}/{$name}/index@index"]
            ]
        ], JSON_PRETTY_PRINT);
    }
}