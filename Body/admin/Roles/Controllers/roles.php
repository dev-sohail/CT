<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Controller.php';

/**
 * Roles Controller
 * 
 * Manage system roles with CRUD operations
 */
class RolesController extends Controller
{
    /**
     * List all roles
     */
    public function index(): void
    {
        // Check admin authentication
        if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
            $_SESSION['login_error'] = 'Admin access required';
            header('Location: /login');
            exit;
        }
        
        // Load model
        $this->load->model('admin/Roles/Roles');
        $roles = $this->model_roles->getAllRoles();
        
        // Prepare data
        $data = [
            'title' => 'Role Management - Admin',
            'roles' => $roles,
            'success' => $_SESSION['success_message'] ?? null,
            'error' => $_SESSION['error_message'] ?? null
        ];
        
        unset($_SESSION['success_message'], $_SESSION['error_message']);
        
        // Load views
        $this->load->view('admin/Common/header', $data);
        $this->load->view('admin/Roles/index', $data);
        $this->load->view('admin/Common/footer', $data);
    }
    
    /**
     * Show create role form
     */
    public function create(): void
    {
        if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
        
        $data = [
            'title' => 'Create Role - Admin',
            'error' => $_SESSION['error_message'] ?? null
        ];
        
        unset($_SESSION['error_message']);
        
        $this->load->view('admin/Common/header', $data);
        $this->load->view('admin/Roles/create', $data);
        $this->load->view('admin/Common/footer', $data);
    }
    
    /**
     * Store new role
     */
    public function store(): void
    {
        if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/roles');
            exit;
        }
        
        $this->load->model('admin/Roles/Roles');
        
        $data = [
            'role_name' => trim($_POST['role_name'] ?? ''),
            'role_slug' => trim($_POST['role_slug'] ?? ''),
            'display_name' => trim($_POST['display_name'] ?? ''),
            'icon' => trim($_POST['icon'] ?? '👤'),
            'color' => trim($_POST['color'] ?? '#667eea'),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        $result = $this->model_roles->createRole($data);
        
        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
            header('Location: /admin/roles');
        } else {
            $_SESSION['error_message'] = $result['message'];
            header('Location: /admin/roles/create');
        }
        exit;
    }
    
    /**
     * Show edit role form
     */
    public function edit(): void
    {
        if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
        
        $roleId = (int)($_GET['id'] ?? 0);
        if ($roleId === 0) {
            header('Location: /admin/roles');
            exit;
        }
        
        $this->load->model('admin/Roles/Roles');
        $role = $this->model_roles->getRoleById($roleId);
        
        if (!$role) {
            $_SESSION['error_message'] = 'Role not found';
            header('Location: /admin/roles');
            exit;
        }
        
        $data = [
            'title' => 'Edit Role - Admin',
            'role' => $role,
            'error' => $_SESSION['error_message'] ?? null
        ];
        
        unset($_SESSION['error_message']);
        
        $this->load->view('admin/Common/header', $data);
        $this->load->view('admin/Roles/edit', $data);
        $this->load->view('admin/Common/footer', $data);
    }
    
    /**
     * Update role
     */
    public function update(): void
    {
        if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/roles');
            exit;
        }
        
        $roleId = (int)($_POST['role_id'] ?? 0);
        if ($roleId === 0) {
            header('Location: /admin/roles');
            exit;
        }
        
        $this->load->model('admin/Roles/Roles');
        
        $data = [
            'role_name' => trim($_POST['role_name'] ?? ''),
            'role_slug' => trim($_POST['role_slug'] ?? ''),
            'display_name' => trim($_POST['display_name'] ?? ''),
            'icon' => trim($_POST['icon'] ?? '👤'),
            'color' => trim($_POST['color'] ?? '#667eea'),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        $result = $this->model_roles->updateRole($roleId, $data);
        
        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
        } else {
            $_SESSION['error_message'] = $result['message'];
        }
        
        header('Location: /admin/roles');
        exit;
    }
    
    /**
     * Delete role
     */
    public function delete(): void
    {
        if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
        
        $roleId = (int)($_GET['id'] ?? 0);
        if ($roleId === 0) {
            header('Location: /admin/roles');
            exit;
        }
        
        $this->load->model('admin/Roles/Roles');
        $result = $this->model_roles->deleteRole($roleId);
        
        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
        } else {
            $_SESSION['error_message'] = $result['message'];
        }
        
        header('Location: /admin/roles');
        exit;
    }
    
    /**
     * Toggle role status
     */
    public function toggle(): void
    {
        if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
        
        $roleId = (int)($_GET['id'] ?? 0);
        if ($roleId === 0) {
            header('Location: /admin/roles');
            exit;
        }
        
        $this->load->model('admin/Roles/Roles');
        $result = $this->model_roles->toggleRoleStatus($roleId);
        
        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
        } else {
            $_SESSION['error_message'] = $result['message'];
        }
        
        header('Location: /admin/roles');
        exit;
    }
}

