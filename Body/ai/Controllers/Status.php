<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Controller.php';

/**
 * AI Status Controller
 */
class StatusController extends Controller
{
    /**
     * Get AI service status
     */
    public function index(): void
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        
        $this->load->model('ai/Status');
        $status = $this->model_status->getStatus();
        
        echo json_encode($status, JSON_PRETTY_PRINT);
    }
    
    /**
     * Get AI capabilities
     */
    public function capabilities(): void
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        
        $this->load->model('ai/Status');
        $capabilities = $this->model_status->getCapabilities();
        
        echo json_encode($capabilities, JSON_PRETTY_PRINT);
    }
}
