<?php
declare(strict_types=1);
require_once ROOT . '/Brain/Core/Controller.php';

class AiController extends Controller
{
    private function checkAuth(): void {
        if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
    }

    public function index(): void {
        $this->checkAuth();
        $this->load->model('admin/AI/AI');
        
        $data = [
            'title' => 'AI Assistant - Admin',
            'stats' => $this->model_ai->getStats(),
            'recentLogs' => $this->model_ai->getRecentLogs(),
            'config' => $this->model_ai->getConfig(),
            'success' => $_SESSION['success_message'] ?? null,
            'error' => $_SESSION['error_message'] ?? null
        ];
        unset($_SESSION['success_message'], $_SESSION['error_message']);
        
        $this->load->view('admin/Common/header', $data);
        $this->load->view('admin/AI/index', $data);
        $this->load->view('admin/Common/footer', $data);
    }

    public function test(): void {
        $this->checkAuth();
        $data = ['title' => 'Test AI - Admin'];
        $this->load->view('admin/Common/header', $data);
        $this->load->view('admin/AI/test', $data);
        $this->load->view('admin/Common/footer', $data);
    }

    public function process(): void {
        $this->checkAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/ai');
            exit;
        }
        
        $this->load->model('admin/AI/AI');
        $prompt = trim($_POST['prompt'] ?? '');
        
        if (empty($prompt)) {
            $_SESSION['error_message'] = 'Prompt cannot be empty';
            header('Location: /admin/ai/test');
            exit;
        }
        
        $result = $this->model_ai->processPrompt($prompt);
        $_SESSION[$result['success'] ? 'ai_response' : 'error_message'] = $result['success'] ? $result['response'] : $result['message'];
        header('Location: /admin/ai/test');
        exit;
    }

    public function clearlogs(): void {
        $this->checkAuth();
        $this->load->model('admin/AI/AI');
        $result = $this->model_ai->clearLogs();
        $_SESSION[$result['success'] ? 'success_message' : 'error_message'] = $result['message'];
        header('Location: /admin/ai');
        exit;
    }
}

