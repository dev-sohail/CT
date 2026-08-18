<?php

namespace Services;

use Delight\Auth\Auth as DelightAuth;
use Delight\Auth\Role;
use PDO;

class AuthService
{
    private $pdo;
    private string $cookieName;
    private int $cookieExpireDays;
    private int $loginThrottleSeconds;
    private int $resetTokenExpireSeconds;
    private array $sessionKeys;
    private AuthLibraryService $lib;

    public function __construct($pdo, array $options = [])
    {
        $this->pdo = $pdo;
        $this->cookieName       = $options['cookie_name']       ?? 'remember_me';
        $this->cookieExpireDays = $options['cookie_expire_days'] ?? 30;
        $this->loginThrottleSeconds = $options['throttle_seconds'] ?? 5;
        $this->resetTokenExpireSeconds = $options['reset_expire'] ?? 3600;
        $this->sessionKeys = [
            'user'         => $options['session_user']    ?? 'user',
            'role'         => $options['session_role']    ?? 'role',
            'logged_in'    => $options['session_logged']  ?? 'logged_in',
            'login_time'   => $options['session_time']    ?? 'login_time',
            'throttle'     => $options['session_throttle'] ?? 'login_throttle',
        ];
        $this->lib = new AuthLibraryService($pdo, [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'throttling' => true,
            'session_resync' => 300,
        ]);
    }

    /* ================================================================
       Registration
       ================================================================ */
    public function register(string $username, string $password, string $email, string $role, array $extra = []): \stdClass
    {
        $result = new \stdClass();
        $result->status = false;

        if ($this->usernameExists($username)) {
            $result->message = 'Username already taken.';
            return $result;
        }
        if ($this->emailExists($email)) {
            $result->message = 'Email already registered.';
            return $result;
        }

        $hash = $this->hashPassword($password);
        $firstName = $extra['first_name'] ?? '';
        $lastName  = $extra['last_name']  ?? '';

        $stmt = $this->pdo->prepare(
            "INSERT INTO users (username, password, email, role, first_name, last_name, phone_number, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'active')"
        );
        $ok = $stmt->execute([
            $username, $hash, $email, $role,
            $firstName, $lastName,
            $extra['phone_number'] ?? null
        ]);

        if ($ok) {
            $userId = (int)$this->pdo->lastInsertId();

            $this->lib->syncAppUserToAuth([
                'user_id' => $userId,
                'email' => $email,
                'password' => $hash,
                'username' => $username,
                'role' => $role,
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $result->status   = true;
            $result->userId   = $userId;
            $result->message  = 'Registration successful.';
        } else {
            $result->message = 'Registration failed. Please try again.';
        }

        return $result;
    }

    /* ================================================================
       Login
       ================================================================ */
    public function login(string $username, string $password, string $role, bool $remember = false, string $ip = ''): \stdClass
    {
        $result = new \stdClass();
        $result->status = false;

        $key = $this->sessionKeys['throttle'];
        if (isset($_SESSION[$key]) && (time() - (int)$_SESSION[$key]) < $this->loginThrottleSeconds) {
            $wait = $this->loginThrottleSeconds - (time() - (int)$_SESSION[$key]);
            $result->message = "Too many attempts. Please wait {$wait} seconds.";
            return $result;
        }

        try {
            $libResult = $this->lib->loginWithUsername($username, $password, $remember);
            if ($libResult->status) {
                $_SESSION[$this->sessionKeys['user']]      = $username;
                $_SESSION[$this->sessionKeys['role']]      = $role;
                $_SESSION[$this->sessionKeys['logged_in']] = true;
                $_SESSION[$this->sessionKeys['login_time']] = time();
                $_SESSION['last_activity'] = time();
                $result->status  = true;
                $result->message = 'Login successful.';
                unset($_SESSION[$key]);
                return $result;
            }
            $result->message = $libResult->message;
        } catch (\Throwable $e) {
            error_log("AuthService login error: " . $e->getMessage());
            $result->message = 'Invalid credentials.';
        }

        $_SESSION[$key] = time();
        return $result;
    }

    /* ================================================================
       Logout
       ================================================================ */
    public function logout(string $username = null, string $role = null): void
    {
        $this->lib->logout();
        if (session_status() === PHP_SESSION_ACTIVE) {
            foreach ($this->sessionKeys as $k) {
                unset($_SESSION[$k]);
            }
            unset($_SESSION['user_id']);
            unset($_SESSION['last_activity']);
        }
    }

    public function enforceSessionTimeout(): void
    {
        $this->lib->enforceSessionTimeout();
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (empty($_SESSION[$this->sessionKeys['logged_in']])) return;
            $timeout = defined('APP_SESSION_TIMEOUT') ? APP_SESSION_TIMEOUT : 1800;
            $last = $_SESSION['last_activity'] ?? $_SESSION[$this->sessionKeys['login_time']] ?? time();
            if ((time() - (int)$last) > $timeout) {
                $user = $_SESSION[$this->sessionKeys['user']] ?? null;
                $role = $_SESSION[$this->sessionKeys['role']] ?? null;
                $this->logout($user, $role);
            } else {
                $_SESSION['last_activity'] = time();
            }
        }
    }

    public function isLoggedIn(): bool
    {
        return $this->lib->isLoggedIn();
    }

    public function getUserId(): ?int
    {
        return $this->lib->getUserId();
    }

    public function getUserRole(): string
    {
        return $this->lib->getUserRole();
    }

    public function attemptRememberMe(string $ip = ''): ?\stdClass
    {
        return $this->lib->attemptRememberMe($ip);
    }

    /* ================================================================
       Password Reset
       ================================================================ */
    public function createResetToken(string $email, string $role): ?\stdClass
    {
        $forgot = $this->lib->forgotPassword($email);
        if ($forgot->status && !empty($forgot->selector) && !empty($forgot->token)) {
            $result = new \stdClass();
            $result->selector = $forgot->selector;
            $result->token = $forgot->token;
            $result->email = $email;
            $result->role = $role;
            return $result;
        }
        return null;
    }

    public function verifyResetToken(string $selector, string $token, string $email, string $role): ?int
    {
        try {
            if ($this->lib->canResetPassword($selector, $token)) {
                $authUserId = $this->lib->getUserId();
                return $authUserId !== null ? (int)$authUserId : null;
            }
        } catch (\Throwable $e) {
            error_log('AuthService verifyResetToken error: ' . $e->getMessage());
        }
        return null;
    }

    public function completeReset(string $selector, string $token, string $email, string $role, string $newPassword): \stdClass
    {
        return $this->lib->resetPassword($selector, $token, $newPassword);
    }

    /* ================================================================
       Helpers
       ================================================================ */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public function usernameExists(string $username): bool
    {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            return $stmt->fetchColumn() > 0;
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function emailExists(string $email): bool
    {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            return $stmt->fetchColumn() > 0;
        } catch (\PDOException $e) {
            return false;
        }
    }
}
