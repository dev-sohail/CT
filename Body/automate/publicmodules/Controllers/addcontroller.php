<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Controller.php';

/**
 * Add Controller Generator
 * 
 * Handles controller creation for public modules
 */
class AddControllerController extends Controller
{
    public function index(): void
    {
        $data = [
            'title' => 'Add Controller',
            'message' => 'Controller generator functionality'
        ];

        $this->loadView('public', 'automate', 'addcontroller', $data);
    }
}