<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Model.php';

/**
 * Public Header Model
 * 
 * Handles public header data operations
 */
class HeaderModel extends Model
{
    protected string $table = 'public_headers';

    /**
     * Get header data
     */
    public function getHeaderData(): array
    {
        return [
            'title' => 'CyberTirah Framework',
            'description' => 'A modern PHP framework for building web applications',
            'navigation' => [
                'Home' => '/',
                'About' => '/about',
                'Contact' => '/contact'
            ]
        ];
    }
}
