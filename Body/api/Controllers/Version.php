<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Controller.php';

/**
 * API Version Controller
 */
class VersionController extends Controller
{
    public function index(): void
    {
        $this->jsonResponse([
            'version' => '2.0.0',
            'framework' => 'CyberTirah',
            'php_version' => PHP_VERSION,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}
