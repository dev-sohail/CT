<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Model.php';

/**
 * Admin Header Model
 * 
 * Handles admin header data operations
 */
class AdminHeaderModel extends Model
{
    protected string $table = 'admin_headers';

    /**
     * Get header data
     */
    public function getHeaderData(): array
    {
        return [
            'title' => 'Admin Panel',
            'version' => '2.0.0',
            'user' => $_SESSION['user'] ?? null
        ];
    }
}
