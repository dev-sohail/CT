<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Controller.php';

/**
 * Add Module Generator
 * 
 * Handles module creation for public
 */
class AddModulesController extends Controller
{
    public function index(): void
    {
        $data = [
            'title' => 'Add Module',
            'message' => 'Module generator functionality'
        ];

        $this->loadView('public', 'automate', 'addmodules', $data);
    }
}