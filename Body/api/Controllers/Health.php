<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Controller.php';

/**
 * API Health Controller
 */
class HealthController extends Controller
{
    public function index(): void
    {
        $this->jsonResponse([
            'status' => 'healthy',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '2.0.0',
            'uptime' => time() - $_SERVER['REQUEST_TIME']
        ]);
    }
}
