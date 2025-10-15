<?php

declare(strict_types=1);

class Csrf
{
    protected string $sessionKey = '_csrf_token';
    protected string $headerName = 'X-CSRF-Token';

    public function __construct(string $sessionKey = '_csrf_token')
    {
        $this->sessionKey = $sessionKey;
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
    }

    public function generateToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION[$this->sessionKey] = $token;
        return $token;
    }

    public function getToken(): string
    {
        return $_SESSION[$this->sessionKey] ?? $this->generateToken();
    }

    public function validate(?string $token = null): bool
    {
        if ($token === null) {
            $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        }
        
        if (empty($token) || !isset($_SESSION[$this->sessionKey])) {
            return false;
        }
        
        return hash_equals($_SESSION[$this->sessionKey], $token);
    }

    public function requireToken(): void
    {
        if (!$this->validate()) {
            http_response_code(403);
            die('CSRF token validation failed');
        }
    }

    public function field(): string
    {
        $token = htmlspecialchars($this->getToken(), ENT_QUOTES, 'UTF-8');
        return "<input type='hidden' name='_csrf' value='{$token}'>";
    }

    public function meta(): string
    {
        $token = htmlspecialchars($this->getToken(), ENT_QUOTES, 'UTF-8');
        return "<meta name='csrf-token' content='{$token}'>";
    }

    public function regenerate(): string
    {
        return $this->generateToken();
    }
}
