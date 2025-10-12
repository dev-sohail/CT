<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Controller.php';

/**
 * Public Home Footer Controller
 */
class FooterController extends Controller
{
    public function index(): void
    {
        $this->set('title', 'Home - Footer');
        $this->loadView('public', 'Home', 'footer');
    }
}

