<?php
declare(strict_types=1);
require_once ROOT . '/Brain/Core/Controller.php';

class DashboardController extends Controller
{
    private function checkAuth(): void {
        if (!isset($_SESSION['logged_in']) || !isset($_SESSION['role'])) {
            header('Location: /login');
            exit;
        }
        
        // Prevent admin from accessing user portal
        if ($_SESSION['role'] === 'admin') {
            header('Location: /admin');
            exit;
        }
    }

    public function index(): void {
        $this->checkAuth();
        $this->load->model('user/Dashboard/Dashboard');
        
        $role = $_SESSION['role'];
        $dashboardData = $this->model_dashboard->getDashboardData($role, $_SESSION['user_id']);
        
        $data = [
            'title' => ucfirst($role) . ' Dashboard - CyberTirah',
            'role' => $role,
            'user' => [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'full_name' => $_SESSION['full_name'] ?? $_SESSION['username'],
                'email' => $_SESSION['email'] ?? '',
                'role' => $role
            ],
            'stats' => $dashboardData['stats'],
            'quickLinks' => $dashboardData['quickLinks'],
            'recentActivity' => $dashboardData['recentActivity']
        ];
        
        $this->load->view('user/Common/header', $data);
        $this->load->view('user/Dashboard/dashboard', $data);
        $this->load->view('user/Common/footer', $data);
    }
}

