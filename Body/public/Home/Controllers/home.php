<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Controller.php';

/**
 * Home Controller
 * 
 * Main homepage controller for the public site
 */
class HomeController extends Controller
{
    /**
     * Display the homepage
     */
    public function index(): void
    {
        // Load model
        $this->load->model('public/Home/Home');
        
        // Get homepage data
        $homeData = $this->model_home->getHomeData();
        
        // Prepare data for view
        $data = [
            'title' => 'Home - CyberTirah Framework',
            'heading' => 'Welcome to CyberTirah Framework',
            'features' => $homeData['features'] ?? [],
            'stats' => $homeData['stats'] ?? [],
            'testimonials' => $homeData['testimonials'] ?? [],
            'cta' => $homeData['cta'] ?? []
        ];
        
        // Load views
        $this->load->view('public/Common/header', $data);
        $this->load->view('public/Home/home', $data);
        $this->load->view('public/Common/footer', $data);
    }
    
    /**
     * Display getting started page
     */
    public function getStarted(): void
    {
        $data = [
            'title' => 'Get Started - CyberTirah Framework',
            'heading' => 'Get Started with CyberTirah'
        ];
        
        $this->load->view('public/Common/header', $data);
        $this->load->view('public/Home/get-started', $data);
        $this->load->view('public/Common/footer', $data);
    }
}
