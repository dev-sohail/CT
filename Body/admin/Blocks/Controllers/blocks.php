<?php
declare(strict_types=1);
require_once ROOT . '/Brain/Core/Controller.php';

class BlocksController extends Controller
{
    private function checkAuth(): void {
        if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
    }

    public function index(): void {
        $this->checkAuth();
        $this->load->model('admin/Blocks/Blocks');
        $status = $_GET['status'] ?? '';
        $location = $_GET['location'] ?? '';
        
        $data = [
            'title' => 'HTML Blocks - Admin',
            'blocks' => $this->model_blocks->getAllBlocks($status, $location),
            'stats' => $this->model_blocks->getStats(),
            'statusFilter' => $status,
            'locationFilter' => $location,
            'success' => $_SESSION['success_message'] ?? null,
            'error' => $_SESSION['error_message'] ?? null
        ];
        unset($_SESSION['success_message'], $_SESSION['error_message']);
        
        $this->load->view('admin/Common/header', $data);
        $this->load->view('admin/Blocks/index', $data);
        $this->load->view('admin/Common/footer', $data);
    }

    public function create(): void {
        $this->checkAuth();
        $data = ['title' => 'Create HTML Block - Admin', 'error' => $_SESSION['error_message'] ?? null];
        unset($_SESSION['error_message']);
        $this->load->view('admin/Common/header', $data);
        $this->load->view('admin/Blocks/create', $data);
        $this->load->view('admin/Common/footer', $data);
    }

    public function store(): void {
        $this->checkAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/blocks');
            exit;
        }
        
        $this->load->model('admin/Blocks/Blocks');
        $result = $this->model_blocks->createBlock([
            'block_name' => trim($_POST['block_name'] ?? ''),
            'block_slug' => trim($_POST['block_slug'] ?? ''),
            'content' => $_POST['content'] ?? '',
            'location' => $_POST['location'] ?? 'global',
            'status' => $_POST['status'] ?? 'inactive'
        ]);
        
        $_SESSION[$result['success'] ? 'success_message' : 'error_message'] = $result['message'];
        header('Location: ' . ($result['success'] ? '/admin/blocks' : '/admin/blocks/create'));
        exit;
    }

    public function edit(): void {
        $this->checkAuth();
        $blockId = (int)($_GET['id'] ?? 0);
        if ($blockId === 0) {
            header('Location: /admin/blocks');
            exit;
        }
        
        $this->load->model('admin/Blocks/Blocks');
        $block = $this->model_blocks->getBlockById($blockId);
        if (!$block) {
            $_SESSION['error_message'] = 'Block not found';
            header('Location: /admin/blocks');
            exit;
        }
        
        $data = ['title' => 'Edit HTML Block - Admin', 'block' => $block, 'error' => $_SESSION['error_message'] ?? null];
        unset($_SESSION['error_message']);
        $this->load->view('admin/Common/header', $data);
        $this->load->view('admin/Blocks/edit', $data);
        $this->load->view('admin/Common/footer', $data);
    }

    public function update(): void {
        $this->checkAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/blocks');
            exit;
        }
        
        $blockId = (int)($_POST['block_id'] ?? 0);
        if ($blockId === 0) {
            header('Location: /admin/blocks');
            exit;
        }
        
        $this->load->model('admin/Blocks/Blocks');
        $result = $this->model_blocks->updateBlock($blockId, [
            'block_name' => trim($_POST['block_name'] ?? ''),
            'block_slug' => trim($_POST['block_slug'] ?? ''),
            'content' => $_POST['content'] ?? '',
            'location' => $_POST['location'] ?? 'global',
            'status' => $_POST['status'] ?? 'inactive'
        ]);
        
        $_SESSION[$result['success'] ? 'success_message' : 'error_message'] = $result['message'];
        header('Location: /admin/blocks');
        exit;
    }

    public function delete(): void {
        $this->checkAuth();
        $blockId = (int)($_GET['id'] ?? 0);
        if ($blockId === 0) {
            header('Location: /admin/blocks');
            exit;
        }
        
        $this->load->model('admin/Blocks/Blocks');
        $result = $this->model_blocks->deleteBlock($blockId);
        $_SESSION[$result['success'] ? 'success_message' : 'error_message'] = $result['message'];
        header('Location: /admin/blocks');
        exit;
    }

    public function toggle(): void {
        $this->checkAuth();
        $blockId = (int)($_GET['id'] ?? 0);
        if ($blockId === 0) {
            header('Location: /admin/blocks');
            exit;
        }
        
        $this->load->model('admin/Blocks/Blocks');
        $result = $this->model_blocks->toggleStatus($blockId);
        $_SESSION[$result['success'] ? 'success_message' : 'error_message'] = $result['message'];
        header('Location: /admin/blocks');
        exit;
    }
}

