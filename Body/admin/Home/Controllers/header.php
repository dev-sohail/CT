<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Controller.php';

/**
 * Admin Home Header Controller
 */
class AdminHeaderController extends Controller
{
    public function index(): void
    {
        $this->set('title', 'Admin - Header');
        $this->loadView('admin', 'Home', 'header');
    }
}

