<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Model.php';

/**
 * Add Models Model
 * 
 * Handles model creation logic
 */
class AddModelsModel extends Model
{
    protected string $table = 'generated_models';

    /**
     * Generate model code
     */
    public function generateModel(string $name, string $module, string $role): string
    {
        $className = ucfirst($name) . 'Model';
        
        return "<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Model.php';

/**
 * {$className}
 * 
 * Generated model for {$module} module
 */
class {$className} extends Model
{
    protected string \$table = '{$name}';

    /**
     * Get all records
     */
    public function getAll(): array
    {
        return \$this->findAll();
    }
}";
    }
}