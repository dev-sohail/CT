<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Controller.php';

/**
 * Users Controller
 * 
 * Manage system users with CRUD operations
 */
class UsersController extends Controller
{
    /**
     * List all users
     */
    public function index(): void
    {
        if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
            $_SESSION['login_error'] = 'Admin access required';
            header('Location: /login');
            exit;
        }
        
        // Get filter parameters
        $roleFilter = $_GET['role'] ?? '';
        $statusFilter = $_GET['status'] ?? '';
        $searchQuery = $_GET['search'] ?? '';
        
        // Load model
        $this->load->model('admin/Users/Users');
        $this->load->model('public/Auth/Auth');
        
        $users = $this->model_users->getAllUsers($roleFilter, $statusFilter, $searchQuery);
        $roles = $this->model_auth->getRoles();
        $stats = $this->model_users->getUserStats();
        
        $data = [
            'title' => 'User Management - Admin',
            'users' => $users,
            'roles' => $roles,
            'stats' => $stats,
            'roleFilter' => $roleFilter,
            'statusFilter' => $statusFilter,
            'searchQuery' => $searchQuery,
            'success' => $_SESSION['success_message'] ?? null,
            'error' => $_SESSION['error_message'] ?? null
        ];
        
        unset($_SESSION['success_message'], $_SESSION['error_message']);
        
        $this->load->view('admin/Common/header', $data);
        $this->load->view('admin/Users/index', $data);
        $this->load->view('admin/Common/footer', $data);
    }
    
    /**
     * Show create user form
     */
    public function create(): void
    {
        if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
        
        $this->load->model('public/Auth/Auth');
        $roles = $this->model_auth->getRoles();
        
        $data = [
            'title' => 'Create User - Admin',
            'roles' => $roles,
            'error' => $_SESSION['error_message'] ?? null
        ];
        
        unset($_SESSION['error_message']);
        
        $this->load->view('admin/Common/header', $data);
        $this->load->view('admin/Users/create', $data);
        $this->load->view('admin/Common/footer', $data);
    }
    
    /**
     * Store new user
     */
    public function store(): void
    {
        if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/users');
            exit;
        }
        
        $this->load->model('admin/Users/Users');
        
        $data = [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone_number' => trim($_POST['phone_number'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'role' => trim($_POST['role'] ?? ''),
            'status' => $_POST['status'] ?? 'active'
        ];
        
        $result = $this->model_users->createUser($data);
        
        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
            header('Location: /admin/users');
        } else {
            $_SESSION['error_message'] = $result['message'];
            header('Location: /admin/users/create');
        }
        exit;
    }
    
    /**
     * Show edit user form
     */
    public function edit(): void
    {
        if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
        
        $userId = (int)($_GET['id'] ?? 0);
        if ($userId === 0) {
            header('Location: /admin/users');
            exit;
        }
        
        $this->load->model('admin/Users/Users');
        $this->load->model('public/Auth/Auth');
        
        $user = $this->model_users->getUserById($userId);
        $roles = $this->model_auth->getRoles();
        
        if (!$user) {
            $_SESSION['error_message'] = 'User not found';
            header('Location: /admin/users');
            exit;
        }
        
        $data = [
            'title' => 'Edit User - Admin',
            'user' => $user,
            'roles' => $roles,
            'error' => $_SESSION['error_message'] ?? null
        ];
        
        unset($_SESSION['error_message']);
        
        $this->load->view('admin/Common/header', $data);
        $this->load->view('admin/Users/edit', $data);
        $this->load->view('admin/Common/footer', $data);
    }
    
    /**
     * Update user
     */
    public function update(): void
    {
        if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/users');
            exit;
        }
        
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId === 0) {
            header('Location: /admin/users');
            exit;
        }
        
        $this->load->model('admin/Users/Users');
        
        $data = [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone_number' => trim($_POST['phone_number'] ?? ''),
            'role' => trim($_POST['role'] ?? ''),
            'status' => $_POST['status'] ?? 'active'
        ];
        
        // Only update password if provided
        if (!empty($_POST['password'])) {
            $data['password'] = $_POST['password'];
        }
        
        $result = $this->model_users->updateUser($userId, $data);
        
        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
        } else {
            $_SESSION['error_message'] = $result['message'];
        }
        
        header('Location: /admin/users');
        exit;
    }
    
    /**
     * Delete user
     */
    public function delete(): void
    {
        if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
        
        $userId = (int)($_GET['id'] ?? 0);
        if ($userId === 0) {
            header('Location: /admin/users');
            exit;
        }
        
        // Prevent admin from deleting themselves
        if ($userId == $_SESSION['user_id']) {
            $_SESSION['error_message'] = 'You cannot delete your own account';
            header('Location: /admin/users');
            exit;
        }
        
        $this->load->model('admin/Users/Users');
        $result = $this->model_users->deleteUser($userId);
        
        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
        } else {
            $_SESSION['error_message'] = $result['message'];
        }
        
        header('Location: /admin/users');
        exit;
    }
    
    /**
     * Toggle user status
     */
    public function toggle(): void
    {
        if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
        
        $userId = (int)($_GET['id'] ?? 0);
        if ($userId === 0) {
            header('Location: /admin/users');
            exit;
        }
        
        $this->load->model('admin/Users/Users');
        $result = $this->model_users->toggleUserStatus($userId);
        
        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
        } else {
            $_SESSION['error_message'] = $result['message'];
        }
        
        header('Location: /admin/users');
        exit;
    }
    
    /**
     * View user details
     */
    public function view(): void
    {
        if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
        
        $userId = (int)($_GET['id'] ?? 0);
        if ($userId === 0) {
            header('Location: /admin/users');
            exit;
        }
        
        $this->load->model('admin/Users/Users');
        $user = $this->model_users->getUserById($userId);
        
        if (!$user) {
            $_SESSION['error_message'] = 'User not found';
            header('Location: /admin/users');
            exit;
        }
        
        $data = [
            'title' => 'View User - Admin',
            'user' => $user
        ];
        
        $this->load->view('admin/Common/header', $data);
        $this->load->view('admin/Users/view', $data);
        $this->load->view('admin/Common/footer', $data);
    }
}

