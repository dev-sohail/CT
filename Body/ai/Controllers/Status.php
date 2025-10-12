<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Controller.php';

/**
 * AI Status Controller
 */
class StatusController extends Controller
{
    public function index(): void
    {
        $this->jsonResponse([
            'ai_status' => 'active',
            'services' => [
                'nlp' => 'available',
                'ml' => 'available',
                'prediction' => 'available'
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}
