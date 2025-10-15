<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Controller.php';

/**
 * About Controller
 * 
 * Handles the About page for the public site
 */
class AboutController extends Controller
{
    /**
     * Display the About page
     */
    public function index(): void
    {
        // Load model
        $this->load->model('public/About/About');
        
        // Get about page data
        $aboutData = $this->model_about->getAboutData();
        
        // Prepare data for view
        $data = [
            'title' => 'About Us - CyberTirah Framework',
            'heading' => 'About CyberTirah Framework',
            'content' => $aboutData['content'] ?? '',
            'team' => $aboutData['team'] ?? [],
            'stats' => $aboutData['stats'] ?? [],
            'mission' => $aboutData['mission'] ?? '',
            'vision' => $aboutData['vision'] ?? ''
        ];
        
        // Load views
        $this->load->view('public/Common/header', $data);
        $this->load->view('public/About/about', $data);
        $this->load->view('public/Common/footer', $data);
    }
    
    /**
     * Display team members
     */
    public function team(): void
    {
        $this->load->model('public/About/About');
        
        $data = [
            'title' => 'Our Team - CyberTirah Framework',
            'heading' => 'Meet Our Team',
            'team' => $this->model_about->getTeamMembers()
        ];
        
        $this->load->view('public/Common/header', $data);
        $this->load->view('public/About/team', $data);
        $this->load->view('public/Common/footer', $data);
    }
    
    /**
     * Display contact information
     */
    public function contact(): void
    {
        $data = [
            'title' => 'Contact Us - CyberTirah Framework',
            'heading' => 'Get In Touch',
            'contact' => [
                'email' => 'info@cybertirah.com',
                'phone' => '+1 (555) 123-4567',
                'address' => '123 Framework Street, Web City, WC 12345'
            ]
        ];
        
        $this->load->view('public/Common/header', $data);
        $this->load->view('public/About/contact', $data);
        $this->load->view('public/Common/footer', $data);
    }
}

