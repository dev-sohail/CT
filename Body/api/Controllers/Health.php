<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Controller.php';

/**
 * API Health Check Controller
 */
class HealthController extends Controller
{
    /**
     * Health check endpoint
     */
    public function index(): void
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        
        $this->load->model('api/Health');
        $health = $this->model_health->check();
        
        http_response_code($health['status'] === 'healthy' ? 200 : 503);
        
        echo json_encode($health, JSON_PRETTY_PRINT);
    }
}
