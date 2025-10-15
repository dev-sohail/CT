<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Model.php';

/**
 * Module Generator Model
 */
class GeneratorModel extends Model
{
    /**
     * Generate a module
     */
    public function generate(string $role, string $module, array $components): array
    {
        $modulePath = ROOT . "/Body/{$role}/{$module}";
        
        // Check if module already exists
        if (is_dir($modulePath)) {
            return [
                'success' => false,
                'message' => "Module {$module} already exists in {$role}"
            ];
        }
        
        try {
            // Create module structure
            $this->createModuleStructure($modulePath, $components);
            
            // Generate components
            if (in_array('controller', $components)) {
                $this->generateController($modulePath, $module);
            }
            
            if (in_array('model', $components)) {
                $this->generateModel($modulePath, $module);
            }
            
            if (in_array('view', $components)) {
                $this->generateView($modulePath, $module);
            }
            
            if (in_array('routes', $components)) {
                $this->generateRoutes($modulePath, $role, $module);
            }
            
            return [
                'success' => true,
                'message' => "Module {$module} created successfully in {$role}!",
                'path' => $modulePath
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Error creating module: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Create module directory structure
     */
    private function createModuleStructure(string $path, array $components): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        
        if (in_array('controller', $components)) {
            mkdir($path . '/Controllers', 0755, true);
        }
        
        if (in_array('model', $components)) {
            mkdir($path . '/Models', 0755, true);
        }
        
        if (in_array('view', $components)) {
            mkdir($path . '/Views', 0755, true);
        }
    }
    
    /**
     * Generate controller
     */
    private function generateController(string $path, string $module): void
    {
        $content = <<<PHP
<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Controller.php';

/**
 * {$module} Controller
 */
class {$module}Controller extends Controller
{
    /**
     * Display index page
     */
    public function index(): void
    {
        \$this->load->model('{$module}');
        \$data = \$this->model_{{strtolower($module)}}->getData();
        
        \$this->load->view('{$module}/index', ['title' => '{$module}', 'data' => \$data]);
    }
}
PHP;
        
        file_put_contents($path . '/Controllers/' . strtolower($module) . '.php', $content);
    }
    
    /**
     * Generate model
     */
    private function generateModel(string $path, string $module): void
    {
        $content = <<<PHP
<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Model.php';

/**
 * {$module} Model
 */
class {$module}Model extends Model
{
    /**
     * Get data
     */
    public function getData(): array
    {
        return [
            'message' => 'Hello from {$module} Model!',
            'timestamp' => time()
        ];
    }
}
PHP;
        
        file_put_contents($path . '/Models/' . $module . 'Model.php', $content);
    }
    
    /**
     * Generate view
     */
    private function generateView(string $path, string $module): void
    {
        $content = <<<HTML
<div style="max-width:1200px;margin:0 auto;padding:20px;">
    <h1><?= htmlspecialchars(\$title ?? '{$module}') ?></h1>
    
    <div style="background:#f8f9fa;padding:20px;border-radius:8px;margin-top:20px;">
        <p>This is a generated view for {$module} module.</p>
        
        <?php if (!empty(\$data)): ?>
            <pre><?= htmlspecialchars(print_r(\$data, true)) ?></pre>
        <?php endif; ?>
    </div>
</div>
HTML;
        
        file_put_contents($path . '/Views/index.ct', $content);
    }
    
    /**
     * Generate routes
     */
    private function generateRoutes(string $path, string $role, string $module): void
    {
        $routePath = '/' . strtolower($module);
        if ($role !== 'public') {
            $routePath = '/' . $role . $routePath;
        }
        
        $content = <<<JSON
{
  "routes": [
    {
      "method": "GET",
      "path": "{$routePath}",
      "handler": "{$role}/{$module}/{$module}@index",
      "name": "{$role}.{strtolower($module)}.index"
    }
  ]
}
JSON;
        
        file_put_contents($path . '/routes.json', $content);
    }
    
    /**
     * List all modules
     */
    public function listModules(): array
    {
        $bodyPath = ROOT . '/Body';
        $modules = [];
        
        $roles = ['public', 'admin', 'api', 'ai', 'automate'];
        
        foreach ($roles as $role) {
            $rolePath = $bodyPath . '/' . $role;
            if (is_dir($rolePath)) {
                $items = scandir($rolePath);
                foreach ($items as $item) {
                    if ($item !== '.' && $item !== '..' && is_dir($rolePath . '/' . $item)) {
                        $modules[] = [
                            'role' => $role,
                            'name' => $item,
                            'path' => $rolePath . '/' . $item
                        ];
                    }
                }
            }
        }
        
        return $modules;
    }
}

