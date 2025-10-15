<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Model.php';

/**
 * Version Information Model
 */
class VersionModel extends Model
{
    /**
     * Get version information
     */
    public function getInfo(): array
    {
        return [
            'framework' => 'CyberTirah Framework',
            'version' => '2.0.0',
            'release_date' => '2025-10-12',
            'php_version' => PHP_VERSION,
            'environment' => getenv('APP_ENV') ?: 'production',
            'features' => [
                'routing' => '✓ Enhanced with caching',
                'registry' => '✓ Dependency injection',
                'loader' => '✓ CodeIgniter-style',
                'auth' => '✓ Multi-role authentication',
                'api' => '✓ RESTful API support',
                'ai' => '✓ AI endpoints',
                'logging' => '✓ Route logging'
            ],
            'endpoints' => [
                'health' => '/api/health',
                'version' => '/api/version',
                'routes' => '/api/routes',
                'stats' => '/api/routes/stats'
            ]
        ];
    }
}

