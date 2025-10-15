<?php
declare(strict_types=1);
require_once ROOT . '/Brain/Core/Controller.php';

class PubliccontentController extends Controller
{
    private function checkAuth(): void {
        if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
    }

    public function index(): void {
        $this->checkAuth();
        $this->load->model('admin/PublicContent/PublicContent');
        
        $data = [
            'title' => 'Public Content Management - Admin',
            'publicModules' => $this->model_publiccontent->getPublicModules(),
            'publishedPages' => $this->model_publiccontent->getPublishedPages(),
            'activeBlocks' => $this->model_publiccontent->getActiveBlocks(),
            'stats' => $this->model_publiccontent->getStats(),
            'success' => $_SESSION['success_message'] ?? null,
            'error' => $_SESSION['error_message'] ?? null
        ];
        unset($_SESSION['success_message'], $_SESSION['error_message']);
        
        $this->load->view('admin/Common/header', $data);
        $this->load->view('admin/PublicContent/index', $data);
        $this->load->view('admin/Common/footer', $data);
    }

    public function settings(): void {
        $this->checkAuth();
        $this->load->model('admin/PublicContent/PublicContent');
        
        $data = [
            'title' => 'Public Site Settings - Admin',
            'settings' => $this->model_publiccontent->getSettings(),
            'error' => $_SESSION['error_message'] ?? null
        ];
        unset($_SESSION['error_message']);
        
        $this->load->view('admin/Common/header', $data);
        $this->load->view('admin/PublicContent/settings', $data);
        $this->load->view('admin/Common/footer', $data);
    }

    public function savesettings(): void {
        $this->checkAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/public-content');
            exit;
        }
        
        $this->load->model('admin/PublicContent/PublicContent');
        $result = $this->model_publiccontent->saveSettings($_POST);
        $_SESSION[$result['success'] ? 'success_message' : 'error_message'] = $result['message'];
        header('Location: /admin/public-content/settings');
        exit;
    }
}

