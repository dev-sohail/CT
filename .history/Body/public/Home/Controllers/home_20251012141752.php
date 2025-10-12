<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Controller.php';

/**
 * Public Home Controller
 */
class HomeController extends Controller
{
    public function index(): void
    {
        $this->set('title', 'Home');
        $this->loadView('public', 'Home', 'header');
        $this->loadView('public', 'Home', 'home');
        $this->loadView('public', 'Home', 'home');
    }
}

