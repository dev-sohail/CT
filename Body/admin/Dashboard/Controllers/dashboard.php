<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Controller.php';

/**
 * Admin Dashboard Controller
 */
class DashboardController extends Controller
{
    /**
     * Display admin dashboard
     */
    public function index(): void
    {
        // Check authentication
        if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
            $_SESSION['login_error'] = 'Admin access required';
            Router::redirect('/login');
            return;
        }
        
        // Load model
        $this->load->model('admin/Dashboard/Dashboard');
        $stats = $this->model_dashboard->getStatistics();
        
        // Prepare data
        $data = [
            'title' => 'Admin Dashboard - CyberTirah',
            'stats' => $stats,
            'user' => $_SESSION['user'] ?? 'Admin'
        ];
        
        // Load views
        $this->load->view('admin/Common/header', $data);
        $this->load->view('admin/Dashboard/index', $data);
        $this->load->view('admin/Common/footer', $data);
    }
    
    /**
     * Display analytics
     */
    public function analytics(): void
    {
        if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
            Router::redirect('/login');
            return;
        }
        
        $this->load->model('admin/Dashboard/Dashboard');
        $analytics = $this->model_dashboard->getAnalytics();
        
        $data = [
            'title' => 'Analytics - Admin',
            'analytics' => $analytics
        ];
        
        $this->load->view('admin/Common/header', $data);
        $this->load->view('admin/Dashboard/analytics', $data);
        $this->load->view('admin/Common/footer', $data);
    }
}

