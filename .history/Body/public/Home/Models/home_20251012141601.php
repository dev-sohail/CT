<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Model.php';

/**
 * Public Home Model
 * 
 * Handles public home data operations
 */
class HomeModel extends Model
{
    protected string $table = 'public_home';

    /**
     * Get home data
     */
    public function getHomeData(): array
    {
        
    }
}
