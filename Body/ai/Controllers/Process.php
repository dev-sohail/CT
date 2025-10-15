<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Controller.php';

/**
 * AI Process Controller
 */
class ProcessController extends Controller
{
    /**
     * Process AI request
     */
    public function index(): void
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        // Get input
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['prompt'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Prompt is required']);
            return;
        }
        
        $this->load->model('ai/Process');
        $result = $this->model_process->process($input['prompt']);
        
        echo json_encode($result, JSON_PRETTY_PRINT);
    }
    
    /**
     * Chat endpoint
     */
    public function chat(): void
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['message'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Message is required']);
            return;
        }
        
        $this->load->model('ai/Process');
        $response = $this->model_process->chat($input['message'], $input['context'] ?? []);
        
        echo json_encode($response, JSON_PRETTY_PRINT);
    }
}
