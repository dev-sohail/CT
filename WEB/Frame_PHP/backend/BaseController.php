<?php

namespace Controllers;

use Services\PermissionService;

class BaseController {
    protected $pdo;
    protected $permissionService;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->permissionService = new PermissionService($this->pdo);
        if (\class_exists(\Services\QueryLogger::class)) {
            \Services\QueryLogger::enable();
        }

        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? '';
        if ($requestMethod && !in_array($requestMethod, ['GET', 'HEAD', 'OPTIONS', 'TRACE'])) {
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            if (!preg_match('#^/api/#', $requestUri)) {
                $this->requireCsrf();
            }
        }
    }

    protected function view($path, $data = []) {
        $data['pdo'] = $this->pdo;
        extract($data);
        $viewFileCt = APP_VIEWS . '/' . $path . '.ct.php';
        $viewFilePhp = APP_VIEWS . '/' . $path . '.php';
        if (file_exists($viewFileCt)) {
            require $viewFileCt;
        } elseif (file_exists($viewFilePhp)) {
            require $viewFilePhp;
        } else {
            die("View file not found: $viewFileCt or $viewFilePhp");
        }
    }

    protected function can($permissionKey) {
        $role = $_SESSION['role'] ?? 'guest';
        return $this->permissionService->can($role, $permissionKey);
    }

    protected function requirePermission($permissionKey, $errorMessage = 'Access denied') {
        if (!$this->can($permissionKey)) {
            http_response_code(403);
            die($errorMessage);
        }
    }

    protected function getCsrfToken() {
        $csrfService = new \Services\CsrfService();
        return $csrfService->generateToken();
    }

    protected function validateCsrfToken($token) {
        $csrfService = new \Services\CsrfService();
        return $csrfService->validateToken($token);
    }

    protected function getRequestCsrfToken(): string {
        if (!empty($_POST['csrf_token'])) return (string)$_POST['csrf_token'];
        if (!empty($_GET['csrf_token'])) return (string)$_GET['csrf_token'];
        $headers = function_exists('getallheaders') ? array_change_key_case(getallheaders(), CASE_LOWER) : [];
        foreach (['x-csrf-token', 'x-csrf', 'x-xsrf-token'] as $h) {
            if (!empty($headers[$h])) return (string)$headers[$h];
        }
        foreach (['HTTP_X_CSRF_TOKEN', 'HTTP_X_CSRF', 'HTTP_X_XSRF_TOKEN'] as $sv) {
            if (!empty($_SERVER[$sv])) return (string)$_SERVER[$sv];
        }
        return '';
    }

    protected function requireCsrf(): void {
        if (!$this->validateCsrfToken($this->getRequestCsrfToken())) {
            http_response_code(403);
            $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
            $xhr = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
            if ($xhr || strpos($accept, 'application/json') !== false) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'invalid_csrf_token']);
            } else {
                echo 'Invalid or expired session token. Please refresh and try again.';
            }
            exit;
        }
    }

    protected function requireAdmin(): void {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || ($_SESSION['role'] ?? '') !== 'admin') {
            header('Location: ' . APP_ADMIN_URL . '/login');
            exit();
        }
    }

    protected function requirePost(string $redirectPath): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $redirectPath);
            exit();
        }
    }

    protected function redirectWith(string $path, array $params = []): void {
        $qs = '';
        if (!empty($params)) {
            $parts = [];
            foreach ($params as $k => $v) {
                if ($v === null) continue;
                $parts[] = urlencode($k) . '=' . urlencode((string)$v);
            }
            if (!empty($parts)) {
                $qs = '?' . implode('&', $parts);
            }
        }
        header('Location: ' . rtrim($path, '?') . $qs);
        exit();
    }
}
