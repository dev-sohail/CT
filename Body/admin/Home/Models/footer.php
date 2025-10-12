<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Model.php';

/**
 * Admin Footer Model
 * 
 * Handles admin footer data operations
 */
class AdminFooterModel extends Model
{
    protected string $table = 'admin_footers';

    /**
     * Get footer data
     */
    public function getFooterData(): array
    {
        return [
            'copyright' => '© ' . date('Y') . ' CyberTirah Framework',
            'version' => '2.0.0',
            'links' => [
                'Documentation' => '/docs',
                'Support' => '/support',
                'GitHub' => 'https://github.com/cybertirah/framework'
            ]
        ];
    }
}
