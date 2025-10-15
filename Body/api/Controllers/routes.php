<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Controller.php';

/**
 * API Routes Controller
 */
class RoutesController extends Controller
{
    /**
     * List all routes endpoint
     */
    public function index(): void
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        
        $logPath = ROOT . '/storage/logs/all_routes.json';
        
        if (file_exists($logPath)) {
            echo file_get_contents($logPath);
        } else {
            http_response_code(404);
            echo json_encode([
                'error' => 'Routes not logged yet',
                'message' => 'Visit the application to generate routes'
            ], JSON_PRETTY_PRINT);
        }
    }
    
    /**
     * Get route statistics
     */
    public function stats(): void
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        
        $stats = Router::getRouteStatistics();
        
        echo json_encode([
            'success' => true,
            'data' => $stats,
            'timestamp' => time()
        ], JSON_PRETTY_PRINT);
    }
}

