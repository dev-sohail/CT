<?php
declare(strict_types=1);
require_once ROOT . '/Brain/Core/Controller.php';

class PagesController extends Controller
{
    private function checkAuth(): void {
        if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
    }

    public function index(): void {
        $this->checkAuth();
        $this->load->model('admin/Pages/Pages');
        $status = $_GET['status'] ?? '';
        $search = $_GET['search'] ?? '';
        
        $data = [
            'title' => 'Page Management - Admin',
            'pages' => $this->model_pages->getAllPages($status, $search),
            'stats' => $this->model_pages->getStats(),
            'statusFilter' => $status,
            'searchQuery' => $search,
            'success' => $_SESSION['success_message'] ?? null,
            'error' => $_SESSION['error_message'] ?? null
        ];
        unset($_SESSION['success_message'], $_SESSION['error_message']);
        
        $this->load->view('admin/Common/header', $data);
        $this->load->view('admin/Pages/index', $data);
        $this->load->view('admin/Common/footer', $data);
    }

    public function create(): void {
        $this->checkAuth();
        $data = ['title' => 'Create Page - Admin', 'error' => $_SESSION['error_message'] ?? null];
        unset($_SESSION['error_message']);
        $this->load->view('admin/Common/header', $data);
        $this->load->view('admin/Pages/create', $data);
        $this->load->view('admin/Common/footer', $data);
    }

    public function store(): void {
        $this->checkAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/pages');
            exit;
        }
        
        $this->load->model('admin/Pages/Pages');
        $result = $this->model_pages->createPage([
            'title' => trim($_POST['title'] ?? ''),
            'slug' => trim($_POST['slug'] ?? ''),
            'content' => $_POST['content'] ?? '',
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'meta_keywords' => trim($_POST['meta_keywords'] ?? ''),
            'status' => $_POST['status'] ?? 'draft',
            'author_id' => $_SESSION['user_id']
        ]);
        
        $_SESSION[$result['success'] ? 'success_message' : 'error_message'] = $result['message'];
        header('Location: ' . ($result['success'] ? '/admin/pages' : '/admin/pages/create'));
        exit;
    }

    public function edit(): void {
        $this->checkAuth();
        $pageId = (int)($_GET['id'] ?? 0);
        if ($pageId === 0) {
            header('Location: /admin/pages');
            exit;
        }
        
        $this->load->model('admin/Pages/Pages');
        $page = $this->model_pages->getPageById($pageId);
        if (!$page) {
            $_SESSION['error_message'] = 'Page not found';
            header('Location: /admin/pages');
            exit;
        }
        
        $data = ['title' => 'Edit Page - Admin', 'page' => $page, 'error' => $_SESSION['error_message'] ?? null];
        unset($_SESSION['error_message']);
        $this->load->view('admin/Common/header', $data);
        $this->load->view('admin/Pages/edit', $data);
        $this->load->view('admin/Common/footer', $data);
    }

    public function update(): void {
        $this->checkAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/pages');
            exit;
        }
        
        $pageId = (int)($_POST['page_id'] ?? 0);
        if ($pageId === 0) {
            header('Location: /admin/pages');
            exit;
        }
        
        $this->load->model('admin/Pages/Pages');
        $result = $this->model_pages->updatePage($pageId, [
            'title' => trim($_POST['title'] ?? ''),
            'slug' => trim($_POST['slug'] ?? ''),
            'content' => $_POST['content'] ?? '',
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'meta_keywords' => trim($_POST['meta_keywords'] ?? ''),
            'status' => $_POST['status'] ?? 'draft'
        ]);
        
        $_SESSION[$result['success'] ? 'success_message' : 'error_message'] = $result['message'];
        header('Location: /admin/pages');
        exit;
    }

    public function delete(): void {
        $this->checkAuth();
        $pageId = (int)($_GET['id'] ?? 0);
        if ($pageId === 0) {
            header('Location: /admin/pages');
            exit;
        }
        
        $this->load->model('admin/Pages/Pages');
        $result = $this->model_pages->deletePage($pageId);
        $_SESSION[$result['success'] ? 'success_message' : 'error_message'] = $result['message'];
        header('Location: /admin/pages');
        exit;
    }

    public function toggle(): void {
        $this->checkAuth();
        $pageId = (int)($_GET['id'] ?? 0);
        if ($pageId === 0) {
            header('Location: /admin/pages');
            exit;
        }
        
        $this->load->model('admin/Pages/Pages');
        $result = $this->model_pages->toggleStatus($pageId);
        $_SESSION[$result['success'] ? 'success_message' : 'error_message'] = $result['message'];
        header('Location: /admin/pages');
        exit;
    }
}

