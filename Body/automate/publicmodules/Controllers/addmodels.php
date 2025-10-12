<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Controller.php';

/**
 * Add Model Generator
 * 
 * Handles model creation for public modules
 */
class AddModelsController extends Controller
{
    public function index(): void
    {
        $data = [
            'title' => 'Add Model',
            'message' => 'Model generator functionality'
        ];

        $this->loadView('public', 'automate', 'addmodels', $data);
    }
}