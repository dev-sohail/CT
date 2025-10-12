<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Model.php';

/**
 * Public Footer Model
 * 
 * Handles public footer data operations
 */
class FooterModel extends Model
{
    protected string $table = 'public_footers';

    /**
     * Get footer data
     */
    public function getFooterData(): array
    {
        return [
            'copyright' => '© ' . date('Y') . ' CyberTirah Framework',
            'version' => '2.0.0',
            'social_links' => [
                'GitHub' => 'https://github.com/cybertirah/framework',
                'Twitter' => 'https://twitter.com/cybertirah',
                'LinkedIn' => 'https://linkedin.com/company/cybertirah'
            ]
        ];
    }
}
