<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Controller.php';

/**
 * Auth Controller
 * 
 * Handles user authentication (login, register, logout, forgot password)
 */
class AuthController extends Controller
{
    /**
     * Redirect to appropriate portal based on role
     */
    private function redirectToPortal(string $role): void
    {
        $url = match($role) {
            'student' => getenv('APP_STPORTAL_URL') ?: '/student/dashboard',
            'teacher' => getenv('APP_TPORTAL_URL') ?: '/teacher/dashboard',
            'admin' => '/admin',
            'parent' => getenv('APP_PPORTAL_URL') ?: '/parent/dashboard',
            'staff' => getenv('APP_SPORTAL_URL') ?: '/staff/dashboard',
            default => '/'
        };
        
        header('Location: ' . $url);
        exit;
    }
    
    /**
     * Display login form
     */
    public function login(): void
    {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            
            // Check if already logged in
            if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
                $this->redirectToPortal($_SESSION['role'] ?? 'admin');
                return;
            }
            
            // Try to load roles via model; fall back to defaults on any error
            $roles = [];
            try {
                if (isset($this->load) && is_object($this->load)) {
                    $this->load->model('public/Auth/Auth');
                    $roles = $this->model_auth->getRoles();
                }
            } catch (Throwable $e) {
                $roles = [
                    ['role_slug' => 'student', 'display_name' => 'Student Login', 'icon' => '👨‍🎓', 'color' => '#667eea'],
                    ['role_slug' => 'teacher', 'display_name' => 'Teacher Login', 'icon' => '👨‍🏫', 'color' => '#764ba2'],
                    ['role_slug' => 'admin', 'display_name' => 'Admin Login', 'icon' => '👨‍💼', 'color' => '#dc3545'],
                    ['role_slug' => 'parent', 'display_name' => 'Parent Login', 'icon' => '👪', 'color' => '#28a745'],
                    ['role_slug' => 'staff', 'display_name' => 'Staff Login', 'icon' => '👔', 'color' => '#ffc107'],
                ];
            }
            
            // Define role selection
            $selectedRole = $_POST['role'] ?? $_SESSION['temp_role'] ?? null;
            
            if (isset($_POST['role']) && !isset($_POST['username'])) {
                $_SESSION['temp_role'] = $selectedRole;
            }
            
            if (isset($_POST['reset'])) {
                $selectedRole = null;
                unset($_SESSION['temp_role']);
            }
            
            // Handle login submission
            if (isset($_POST['username'], $_POST['password'], $_POST['role'])) {
                $this->processLogin();
                return;
            }
            
            // Prepare data
            $data = [
                'title' => 'Login - CyberTirah Framework',
                'roles' => $roles,
                'selectedRole' => $selectedRole,
                'error' => $_SESSION['login_error'] ?? null,
                'success' => $_SESSION['login_success'] ?? null
            ];
            
            unset($_SESSION['login_error'], $_SESSION['login_success']);
            
            // Render login view; on failure, show inline fallback
            try {
                if (isset($this->load) && is_object($this->load)) {
                    $this->load->view('public/Auth/login', $data);
                    return;
                }
            } catch (Throwable $e) {
                // fall through to inline fallback
            }
            
            // Inline minimal fallback UI (no dependencies)
            echo '<div style="min-height:80vh;display:flex;align-items:center;justify-content:center;padding:20px;font-family:-apple-system,BlinkMacSystemFont,\' . "'" . 'Segoe UI' . "'" . ',Roboto,sans-serif;">'
                . '<div style="max-width:500px;width:100%;background:white;border-radius:15px;box-shadow:0 10px 40px rgba(0,0,0,0.1);padding:40px;">'
                . '<h2 style="text-align:center;color:#333;margin:0 0 10px;">Login</h2>'
                . (!empty($data['error']) ? ('<div style="padding:12px;background:#f8d7da;border:1px solid #f5c2c7;border-radius:8px;margin-bottom:20px;color:#842029;">' . htmlspecialchars((string)$data['error']) . '</div>') : '')
                . (!empty($data['success']) ? ('<div style="padding:12px;background:#d1e7dd;border:1px solid #badbcc;border-radius:8px;margin-bottom:20px;color:#0f5132;">' . htmlspecialchars((string)$data['success']) . '</div>') : '')
                . '<form method="post" action="/login">'
                . '<input type="hidden" name="role" value="' . htmlspecialchars((string)($selectedRole ?? 'admin')) . '">'
                . '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:6px;">Username or Email</label><input name="username" required style="width:100%;padding:10px;border:1px solid #e9ecef;border-radius:8px;"></div>'
                . '<div style="margin-bottom:16px;"><label style="display:block;margin-bottom:6px;">Password</label><input type="password" name="password" required style="width:100%;padding:10px;border:1px solid #e9ecef;border-radius:8px;"></div>'
                . '<button type="submit" style="width:100%;padding:12px;border:none;border-radius:8px;background:#667eea;color:white;font-weight:600;cursor:pointer;">Login</button>'
                . '</form>'
                . '<div style="text-align:center;margin-top:12px;"><a href="/forgot-password" style="color:#667eea;text-decoration:none;">Forgot Password?</a></div>'
                . '<div style="text-align:center;margin-top:12px;"><a href="/register" style="color:#667eea;text-decoration:none;">Register</a></div>'
                . '</div></div>';
        } catch (Throwable $e) {
            // Absolute last-resort fallback UI
            echo '<div style="margin: 2rem auto; max-width: 600px; padding: 2rem; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 0.5rem; font-family: sans-serif;">'
                . '<h2 style="color:#dc3545;margin-top:0;">Login</h2>'
                . '<p style="color:#6c757d;">A temporary error occurred. Please try again.</p>'
                . '<form method="post" action="/login">'
                . '<input type="hidden" name="role" value="admin">'
                . '<div><input name="username" placeholder="Username or Email" required style="width:100%;padding:10px;margin-bottom:8px;"></div>'
                . '<div><input type="password" name="password" placeholder="Password" required style="width:100%;padding:10px;margin-bottom:8px;"></div>'
                . '<button type="submit" style="padding:10px 16px;background:#667eea;color:#fff;border:none;border-radius:6px;">Login</button>'
                . '</form>'
                . '</div>';
        }
    }
    
    /**
     * Display register form
     */
    public function register(): void
    {
        // Check if already logged in
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            $this->redirectToPortal($_SESSION['role'] ?? '');
            return;
        }
        
        // Define role selection
        $selectedRole = $_POST['role'] ?? $_SESSION['temp_role'] ?? null;
        
        if (isset($_POST['role']) && !isset($_POST['first_name'])) {
            $_SESSION['temp_role'] = $selectedRole;
        }
        
        if (isset($_POST['reset'])) {
            $selectedRole = null;
            unset($_SESSION['temp_role']);
        }
        
        // Handle registration submission
        if (isset($_POST['first_name'], $_POST['last_name'], $_POST['password'], $_POST['role'])) {
            $this->processRegistration();
            return;
        }
        
        // Prepare data
        $data = [
            'title' => 'Register - CyberTirah Framework',
            'selectedRole' => $selectedRole,
            'error' => $_SESSION['register_error'] ?? null,
            'success' => $_SESSION['register_success'] ?? null
        ];
        
        unset($_SESSION['register_error'], $_SESSION['register_success']);
        
        // Load views
        $this->load->view('public/Common/header', $data);
        $this->load->view('public/Auth/register', $data);
        $this->load->view('public/Common/footer', $data);
    }
    
    /**
     * Handle logout
     */
    public function logout(): void
    {
        // Destroy session
        session_unset();
        session_destroy();
        $_SESSION = [];
        
        // Start new session for message
        session_start();
        $_SESSION['logout_success'] = 'You have been logged out successfully.';
        
        // Redirect to home
        header('Location: /');
        exit;
    }
    
    /**
     * Display forgot password form
     */
    public function forgotPassword(): void
    {
        // Handle forgot password submission
        if (isset($_POST['email'], $_POST['role'])) {
            $this->processForgotPassword();
            return;
        }
        
        // Prepare data
        $data = [
            'title' => 'Forgot Password - CyberTirah Framework',
            'error' => $_SESSION['forgot_error'] ?? null,
            'success' => $_SESSION['forgot_success'] ?? null
        ];
        
        unset($_SESSION['forgot_error'], $_SESSION['forgot_success']);
        
        // Load views
        $this->load->view('public/Common/header', $data);
        $this->load->view('public/Auth/forgot', $data);
        $this->load->view('public/Common/footer', $data);
    }
    
    /**
     * Process login
     */
    private function processLogin(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        
        // Ensure loader is available
        if (!isset($this->load) || !is_object($this->load)) {
            $_SESSION['login_error'] = 'Login service unavailable. Please try again later.';
            header('Location: /login');
            exit;
        }
        
        $this->load->model('public/Auth/Auth');
        
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $role = trim($_POST['role']);
        
        $result = $this->model_auth->authenticate($username, $password, $role);
        
        if ($result['success']) {
            // Store complete user data in session
            $user = $result['user'];
            
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['status'] = $user['status'];
            $_SESSION['last_login'] = $user['last_login'];
            $_SESSION['login_time'] = time();
            
            // For backward compatibility
            $_SESSION['user'] = $user['username'];
            
            unset($_SESSION['temp_role']);
            
            // Redirect to portal
            $this->redirectToPortal($user['role']);
        } else {
            $_SESSION['login_error'] = $result['message'];
            header('Location: /login');
            exit;
        }
    }
    
    /**
     * Process registration
     */
    private function processRegistration(): void
    {
        $this->load->model('public/Auth/Auth');
        
        $data = [
            'first_name' => trim($_POST['first_name']),
            'last_name' => trim($_POST['last_name']),
            'email' => trim($_POST['email']),
            'phone' => trim($_POST['phone'] ?? ''),
            'password' => $_POST['password'],
            'role' => trim($_POST['role'])
        ];
        
        $result = $this->model_auth->register($data);
        
        if ($result['success']) {
            $_SESSION['login_success'] = 'Registration successful! Please login.';
            unset($_SESSION['temp_role']);
            header('Location: /login');
            exit;
        } else {
            $_SESSION['register_error'] = $result['message'];
            header('Location: /register');
            exit;
        }
    }
    
    /**
     * Process forgot password
     */
    private function processForgotPassword(): void
    {
        $this->load->model('public/Auth/Auth');
        
        $email = trim($_POST['email']);
        $role = trim($_POST['role']);
        
        $result = $this->model_auth->resetPassword($email, $role);
        
        if ($result['success']) {
            $_SESSION['forgot_success'] = $result['message'];
        } else {
            $_SESSION['forgot_error'] = $result['message'];
        }
        
        header('Location: /forgot-password');
        exit;
    }
}

