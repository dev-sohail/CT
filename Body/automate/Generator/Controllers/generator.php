<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Controller.php';

/**
 * Module Generator Controller
 */
class GeneratorController extends Controller
{
    /**
     * Display generator form
     */
    public function index(): void
    {
        $data = [
            'title' => 'Module Generator - CyberTirah',
            'roles' => ['public', 'admin', 'api', 'ai']
        ];
        
        $this->load->view('automate/Generator/index', $data);
    }
    
    /**
     * Generate module
     */
    public function generate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Router::redirect('/automate');
            return;
        }
        
        $role = $_POST['role'] ?? '';
        $module = $_POST['module'] ?? '';
        $components = $_POST['components'] ?? [];
        
        if (empty($role) || empty($module)) {
            $_SESSION['error'] = 'Role and Module name are required';
            Router::redirect('/automate');
            return;
        }
        
        $this->load->model('automate/Generator/Generator');
        $result = $this->model_generator->generate($role, $module, $components);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
        
        Router::redirect('/automate');
    }
    
    /**
     * List generated modules
     */
    public function list(): void
    {
        $this->load->model('automate/Generator/Generator');
        $modules = $this->model_generator->listModules();
        
        $data = [
            'title' => 'Generated Modules',
            'modules' => $modules
        ];
        
        $this->load->view('automate/Generator/list', $data);
    }
}

