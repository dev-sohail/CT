<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Controller.php';

/**
 * Public Home Header Controller
 */
class HeaderController extends Controller
{
    public function index(): void
    {
        $this->set('title', 'Home - Header');
        $this->loadView('public', 'Home', 'header');
    }
}

