<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Controller.php';

/**
 * Add Module Generator
 * 
 * Handles module creation for admin
 */
class AddModulesController extends Controller
{
    public function index(): void
    {
        $data = [
            'title' => 'Add Module',
            'message' => 'Module generator functionality'
        ];

        $this->loadView('admin', 'automate', 'addmodules', $data);
    }
}