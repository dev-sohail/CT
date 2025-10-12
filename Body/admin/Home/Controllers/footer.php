<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Controller.php';

/**
 * Admin Home Footer Controller
 */
class AdminFooterController extends Controller
{
    public function index(): void
    {
        $this->set('title', 'Admin - Footer');
        $this->loadView('admin', 'Home', 'footer');
    }
}

