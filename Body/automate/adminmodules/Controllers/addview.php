<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Controller.php';

/**
 * Add View Generator
 * 
 * Handles view creation for admin modules
 */
class AddViewController extends Controller
{
    public function index(): void
    {
        $data = [
            'title' => 'Add View',
            'message' => 'View generator functionality'
        ];

        $this->loadView('admin', 'automate', 'addview', $data);
    }
}