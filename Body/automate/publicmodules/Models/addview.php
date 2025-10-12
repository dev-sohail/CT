<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Model.php';

/**
 * Add View Model
 * 
 * Handles view creation logic for public modules
 */
class AddViewModel extends Model
{
    protected string $table = 'generated_views';

    /**
     * Generate view code
     */
    public function generateView(string $name, string $module, string $role): string
    {
        return "<div class=\"container\">
    <h1><?= htmlspecialchars(\$title ?? '{$name}') ?></h1>
    <div class=\"content\">
        <p>This is the {$name} view for the {$module} module.</p>
    </div>
</div>";
    }
}