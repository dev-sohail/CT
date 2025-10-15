<?php
declare(strict_types=1);
require_once ROOT . '/Brain/Core/Controller.php';

class ModulegeneratorController extends Controller
{
    private function checkAuth(): void {
        if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
    }

    public function index(): void {
        $this->checkAuth();
        $data = [
            'title' => 'Module Generator - Admin',
            'success' => $_SESSION['success_message'] ?? null,
            'error' => $_SESSION['error_message'] ?? null
        ];
        unset($_SESSION['success_message'], $_SESSION['error_message']);
        
        $this->load->view('admin/Common/header', $data);
        $this->load->view('admin/ModuleGenerator/index', $data);
        $this->load->view('admin/Common/footer', $data);
    }

    public function generate(): void {
        $this->checkAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/module-generator');
            exit;
        }
        
        $this->load->model('admin/ModuleGenerator/ModuleGenerator');
        $result = $this->model_modulegenerator->generateModule([
            'module_name' => trim($_POST['module_name'] ?? ''),
            'module_type' => $_POST['module_type'] ?? 'public',
            'include_model' => isset($_POST['include_model']),
            'include_view' => isset($_POST['include_view']),
            'create_crud' => isset($_POST['create_crud']),
            'table_name' => trim($_POST['table_name'] ?? '')
        ]);
        
        $_SESSION[$result['success'] ? 'success_message' : 'error_message'] = $result['message'];
        header('Location: /admin/module-generator');
        exit;
    }
}

