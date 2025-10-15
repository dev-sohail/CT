<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Controller.php';

/**
 * API Version Controller
 */
class VersionController extends Controller
{
    /**
     * Version information endpoint
     */
    public function index(): void
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        
        $this->load->model('api/Version');
        $version = $this->model_version->getInfo();
        
        echo json_encode($version, JSON_PRETTY_PRINT);
    }
}
