<?php
namespace Services;

class CsrfService {
    private const TOKEN_NAME = 'csrf_token';
    private const TOKEN_LIFETIME = 3600; // 1 hour

    public function generateToken() {
        if (!isset($_SESSION[self::TOKEN_NAME])) {
            $_SESSION[self::TOKEN_NAME] = bin2hex(random_bytes(32));
            $_SESSION[self::TOKEN_NAME . '_time'] = time();
        }
        return $_SESSION[self::TOKEN_NAME];
    }

    public function validateToken($token) {
        if (!isset($_SESSION[self::TOKEN_NAME])) {
            return false;
        }
        // Check token lifetime
        if (isset($_SESSION[self::TOKEN_NAME . '_time'])) {
            $age = time() - $_SESSION[self::TOKEN_NAME . '_time'];
            if ($age > self::TOKEN_LIFETIME) {
                $this->regenerateToken();
                return false;
            }
        }
        return hash_equals($_SESSION[self::TOKEN_NAME], (string)$token);
    }

    public function regenerateToken() {
        unset($_SESSION[self::TOKEN_NAME]);
        unset($_SESSION[self::TOKEN_NAME . '_time']);
        return $this->generateToken();
    }

    public function getTokenName() {
        return self::TOKEN_NAME;
    }
}
