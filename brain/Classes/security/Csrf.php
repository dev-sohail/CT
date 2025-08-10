<?php
/**
 * Class Csrf
 *
 * Provides Cross-Site Request Forgery (CSRF) protection by generating and validating CSRF tokens.
 */
class Csrf
{
    protected string $sessionKey = '_csrf_token';

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Generate a CSRF token and store it in session.
     */
    public function generateToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION[$this->sessionKey] = $token;
        return $token;
    }

    /**
     * Get the current CSRF token, generating one if necessary.
     */
    public function getToken(): string
    {
        if (!isset($_SESSION[$this->sessionKey])) {
            return $this->generateToken();
        }
        return $_SESSION[$this->sessionKey];
    }

    /**
     * Validate a given CSRF token against the stored session token.
     */
    public function validateToken(?string $token): bool
    {
        return isset($_SESSION[$this->sessionKey]) && hash_equals($_SESSION[$this->sessionKey], (string)$token);
    }

    /**
     * Embed a hidden input field with the CSRF token for HTML forms.
     */
    public function getHiddenInput(): string
    {
        $token = htmlspecialchars($this->getToken(), ENT_QUOTES, 'UTF-8');
        return "<input type='hidden' name='_csrf' value='{$token}'>";
    }
}
