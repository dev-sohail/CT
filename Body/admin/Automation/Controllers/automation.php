<?php
declare(strict_types=1);
require_once ROOT . '/Brain/Core/Controller.php';

class AutomationController extends Controller
{
    private function checkAuth(): void {
        if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
    }

    public function index(): void {
        $this->checkAuth();
        $this->load->model('admin/Automation/Automation');
        
        $data = [
            'title' => 'Automation Center - Admin',
            'modules' => $this->model_automation->listModules(),
            'stats' => $this->model_automation->getStats(),
            'success' => $_SESSION['success_message'] ?? null,
            'error' => $_SESSION['error_message'] ?? null
        ];
        unset($_SESSION['success_message'], $_SESSION['error_message']);
        
        $this->load->view('admin/Common/header', $data);
        $this->load->view('admin/Automation/index', $data);
        $this->load->view('admin/Common/footer', $data);
    }

    public function clearcache(): void {
        $this->checkAuth();
        $cacheDir = ROOT . '/Storage/cache/';
        $cleared = 0;
        
        foreach (glob($cacheDir . '*.php') as $file) {
            if (unlink($file)) $cleared++;
        }
        foreach (glob($cacheDir . '*.json') as $file) {
            if (unlink($file)) $cleared++;
        }
        
        $_SESSION['success_message'] = "Cleared {$cleared} cache files successfully";
        header('Location: /admin/automation');
        exit;
    }

    public function regenerateroutes(): void {
        $this->checkAuth();
        $cacheFile = ROOT . '/Storage/cache/routes.php';
        
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
            $_SESSION['success_message'] = 'Route cache cleared. Routes will be regenerated on next request.';
        } else {
            $_SESSION['error_message'] = 'Route cache does not exist.';
        }
        
        header('Location: /admin/automation');
        exit;
    }

    public function scanmodules(): void {
        $this->checkAuth();
        $this->load->model('admin/Automation/Automation');
        $result = $this->model_automation->scanAndUpdateModules();
        $_SESSION[$result['success'] ? 'success_message' : 'error_message'] = $result['message'];
        header('Location: /admin/automation');
        exit;
    }
}

