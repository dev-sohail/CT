<?php

namespace Services;

use Delight\Auth\Auth as DelightAuth;
use Delight\Auth\Role;
use PDO;

class AuthLibraryService
{
    private const ROLE_MAP = [
        'admin'    => Role::ADMIN,
        'teacher'  => Role::DEVELOPER,
        'student'  => Role::SUBSCRIBER,
        'parent'   => Role::CONSUMER,
        'staff'    => Role::EMPLOYEE,
    ];

    private const APP_ROLE_MAP = [
        Role::ADMIN      => 'admin',
        Role::DEVELOPER  => 'teacher',
        Role::SUBSCRIBER => 'student',
        Role::CONSUMER   => 'parent',
        Role::EMPLOYEE   => 'staff',
    ];

    private PDO $pdo;
    private DelightAuth $auth;

    public function __construct(PDO $pdo, array $options = [])
    {
        $this->pdo = $pdo;
        $ipAddress = $options['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? null);
        $throttling = $options['throttling'] ?? true;
        $sessionResync = $options['session_resync'] ?? 300;

        $this->auth = new DelightAuth($pdo, $ipAddress, 'auth_', $throttling, $sessionResync);
    }

    public function getAuth(): DelightAuth {
        return $this->auth;
    }

    private function roleMask(string $role): int {
        return self::ROLE_MAP[$role] ?? 0;
    }

    private function appRole(int $mask): string {
        if ($mask === 0) return 'guest';
        foreach (self::APP_ROLE_MAP as $bit => $name) {
            if ($mask & $bit) return $name;
        }
        return 'guest';
    }

    public function syncAppUserToAuth(array $user): void {
        $mask = $this->roleMask((string)($user['role'] ?? ''));
        $registered = isset($user['created_at']) ? strtotime((string)$user['created_at']) : time();

        $stmt = $this->pdo->prepare("
            INSERT INTO auth_users (id, email, password, username, status, verified, resettable, roles_mask, registered)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                email = VALUES(email),
                password = VALUES(password),
                username = VALUES(username),
                status = VALUES(status),
                roles_mask = VALUES(roles_mask),
                registered = VALUES(registered)
        ");
        $stmt->execute([
            (int)($user['user_id'] ?? 0),
            (string)($user['email'] ?? ''),
            (string)($user['password'] ?? ''),
            (string)($user['username'] ?? ''),
            (int)($user['status'] ?? 1),
            1,
            1,
            $mask,
            $registered,
        ]);
    }

    public function register(string $email, string $password, ?string $username, string $role, array $extra = []): \stdClass {
        $result = new \stdClass();
        $result->status = false;

        try {
            $userId = $this->auth->register($email, $password, $username, function ($selector, $token) use ($email) {
                app_log("Email verification needed for {$email}", ['selector' => $selector], 'INFO', 'auth');
            });
            $result->status = true;
            $result->userId = $userId;
            $result->message = 'Registration successful. Please verify your email.';
        } catch (\Delight\Auth\InvalidEmailException $e) {
            $result->message = 'Invalid email address.';
        } catch (\Delight\Auth\InvalidPasswordException $e) {
            $result->message = 'Invalid password.';
        } catch (\Delight\Auth\UserAlreadyExistsException $e) {
            $result->message = 'User already exists.';
        } catch (\Delight\Auth\TooManyRequestsException $e) {
            $result->message = 'Too many requests. Please try again later.';
        } catch (\Throwable $e) {
            $result->message = 'Registration failed. Please try again.';
            error_log('AuthLibrary register error: ' . $e->getMessage());
        }

        if ($result->status && !empty($extra)) {
            $this->createAppUser((int)$result->userId, $email, $password, $username, $role, $extra);
        }

        return $result;
    }

    public function login(string $email, string $password, bool $remember = null, int $rememberDuration = null): \stdClass {
        $result = new \stdClass();
        $result->status = false;

        try {
            $this->auth->login($email, $password, $remember === true ? ($rememberDuration ?? (365 * 24 * 60 * 60)) : null);
            $result->status = true;
            $result->message = 'Login successful.';

            $authUserId = (int)$this->auth->getUserId();
            $appUser = $this->findAppUserByAuthId($authUserId);
            if ($appUser) {
                $this->syncAppSession($appUser);
            }
        } catch (\Delight\Auth\InvalidEmailException $e) {
            $result->message = 'Invalid email address.';
        } catch (\Delight\Auth\InvalidPasswordException $e) {
            $result->message = 'Invalid credentials.';
        } catch (\Delight\Auth\EmailNotVerifiedException $e) {
            $result->message = 'Email not verified.';
        } catch (\Delight\Auth\TooManyRequestsException $e) {
            $result->message = 'Too many requests. Please try again later.';
        } catch (\Throwable $e) {
            $result->message = 'Login failed. Please try again.';
            error_log('AuthLibrary login error: ' . $e->getMessage());
        }

        return $result;
    }

    public function loginWithUsername(string $username, string $password, bool $remember = null, int $rememberDuration = null): \stdClass {
        $result = new \stdClass();
        $result->status = false;

        try {
            $this->auth->loginWithUsername($username, $password, $remember === true ? ($rememberDuration ?? (365 * 24 * 60 * 60)) : null);
            $result->status = true;
            $result->message = 'Login successful.';

            $authUserId = (int)$this->auth->getUserId();
            $appUser = $this->findAppUserByAuthId($authUserId);
            if ($appUser) {
                $this->syncAppSession($appUser);
            }
        } catch (\Delight\Auth\UnknownUsernameException $e) {
            $result->message = 'Invalid credentials.';
        } catch (\Delight\Auth\AmbiguousUsernameException $e) {
            $result->message = 'Ambiguous username.';
        } catch (\Delight\Auth\InvalidPasswordException $e) {
            $result->message = 'Invalid credentials.';
        } catch (\Delight\Auth\EmailNotVerifiedException $e) {
            $result->message = 'Email not verified.';
        } catch (\Delight\Auth\TooManyRequestsException $e) {
            $result->message = 'Too many requests. Please try again later.';
        } catch (\Throwable $e) {
            $result->message = 'Login failed. Please try again.';
            error_log('AuthLibrary loginWithUsername error: ' . $e->getMessage());
        }

        return $result;
    }

    public function logout(): void {
        try {
            $this->auth->logOut();
        } catch (\Throwable $e) {
            error_log('AuthLibrary logout error: ' . $e->getMessage());
        }
        $this->clearAppSession();
    }

    public function forgotPassword(string $email): \stdClass
    {
        $result = new \stdClass();
        $result->status = false;

        try {
            $selector = null;
            $token = null;
            $this->auth->forgotPassword($email, function ($s, $t) use (&$selector, &$token) {
                $selector = $s;
                $token = $t;
            });
            $result->status = true;
            $result->selector = $selector;
            $result->token = $token;
            $result->message = 'If an account exists, a reset link has been sent.';
        } catch (\Delight\Auth\InvalidEmailException $e) {
            $result->message = 'Invalid email address.';
        } catch (\Delight\Auth\EmailNotVerifiedException $e) {
            $result->message = 'Email not verified.';
        } catch (\Delight\Auth\ResetDisabledException $e) {
            $result->message = 'Password reset is disabled.';
        } catch (\Delight\Auth\TooManyRequestsException $e) {
            $result->message = 'Too many requests. Please try again later.';
        } catch (\Throwable $e) {
            $result->message = 'Failed to send reset email. Please try again.';
            error_log('AuthLibrary forgotPassword error: ' . $e->getMessage());
        }

        return $result;
    }

    public function canResetPassword(string $selector, string $token): bool {
        try {
            return $this->auth->canResetPassword($selector, $token);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function resetPassword(string $selector, string $token, string $newPassword): \stdClass {
        $result = new \stdClass();
        $result->status = false;

        try {
            $this->auth->resetPassword($selector, $token, $newPassword);
            $result->status = true;
            $result->message = 'Password has been reset.';
        } catch (\Delight\Auth\InvalidSelectorTokenPairException $e) {
            $result->message = 'Invalid or expired reset token.';
        } catch (\Delight\Auth\TokenExpiredException $e) {
            $result->message = 'Reset token has expired.';
        } catch (\Delight\Auth\InvalidPasswordException $e) {
            $result->message = 'Invalid password.';
        } catch (\Delight\Auth\ResetDisabledException $e) {
            $result->message = 'Password reset is disabled.';
        } catch (\Delight\Auth\TooManyRequestsException $e) {
            $result->message = 'Too many requests. Please try again later.';
        } catch (\Throwable $e) {
            $result->message = 'Failed to reset password. Please try again.';
            error_log('AuthLibrary resetPassword error: ' . $e->getMessage());
        }

        return $result;
    }

    public function isLoggedIn(): bool {
        try {
            return $this->auth->isLoggedIn();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getUserId(): ?int {
        try {
            $id = $this->auth->getUserId();
            return $id !== null ? (int)$id : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getUserRole(): string {
        try {
            if (!$this->auth->isLoggedIn()) return 'guest';
            $userId = (int)$this->auth->getUserId();
            $appUser = $this->findAppUserByAuthId($userId);
            if ($appUser) {
                return (string)($appUser['role'] ?? 'guest');
            }
            $mask = 0;
            try {
                $mask = (int)$this->auth->getRolesMask();
            } catch (\Throwable $e) {}
            return $this->appRole($mask);
        } catch (\Throwable $e) {
            return 'guest';
        }
    }

    public function getUserEmail(): ?string {
        try {
            return $this->auth->getEmail();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getUsername(): ?string {
        try {
            return $this->auth->getUsername();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function enforceSessionTimeout(): void {
        if (!$this->isLoggedIn()) return;

        $timeout = defined('APP_SESSION_TIMEOUT') ? APP_SESSION_TIMEOUT : 1800;
        $last = $_SESSION['last_activity'] ?? ($_SESSION['login_time'] ?? time());

        if ((time() - (int)$last) > $timeout) {
            $this->logout();
        } else {
            $_SESSION['last_activity'] = time();
        }
    }

    public function attemptRememberMe(string $ip = ''): ?\stdClass {
        try {
            $result = $this->auth->attemptRememberMe();
            if ($result && !empty($result['id'])) {
                $appUser = $this->findAppUserByAuthId((int)$result['id']);
                if ($appUser) {
                    $this->syncAppSession($appUser);
                }
                return $result;
            }
        } catch (\Throwable $e) {
            error_log('AuthLibrary remember-me error: ' . $e->getMessage());
        }
        return null;
    }

    public function approveRegistrationRequest(int $requestId): \stdClass {
        $result = new \stdClass();
        $result->status = false;

        try {
            $stmt = $this->pdo->prepare("SELECT * FROM registration_requests WHERE id = ? AND status = 'pending' LIMIT 1");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                $result->message = 'Pending registration request not found.';
                return $result;
            }

            $username = $request['username'];
            $this->syncAppUserToAuth([
                'user_id' => 0,
                'email' => $request['email'],
                'password' => $request['password_hash'],
                'username' => $username,
                'role' => $request['role'],
                'first_name' => $request['first_name'],
                'last_name' => $request['last_name'],
                'phone_number' => $request['phone_number'],
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $authUserId = null;
            try {
                $authUserId = $this->auth->admin()->createUser($request['email'], $request['password_hash'], $username);
            } catch (\Throwable $e) {
                error_log('AuthLibrary createUser error: ' . $e->getMessage());
            }

            if ($authUserId) {
                $up = $this->pdo->prepare("UPDATE auth_users SET roles_mask = ?, registered = ? WHERE id = ?");
                $up->execute([$this->roleMask($request['role']), time(), $authUserId]);
            }

            $appUser = $this->findAppUserByEmailAndRole($request['email'], $request['role']);
            if (!$appUser) {
                $ins = $this->pdo->prepare("
                    INSERT INTO users (username, password, email, role, first_name, last_name, phone_number, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
                ");
                $ins->execute([
                    $username,
                    $request['password_hash'],
                    $request['email'],
                    $request['role'],
                    $request['first_name'],
                    $request['last_name'],
                    $request['phone_number'],
                ]);
            }

            $this->pdo->prepare("UPDATE registration_requests SET status = 'approved', reviewed_at = NOW() WHERE id = ?")
                ->execute([$requestId]);

            $result->status = true;
            $result->message = 'Registration request approved.';
        } catch (\Throwable $e) {
            $result->message = 'Failed to approve request.';
            error_log('AuthLibrary approve error: ' . $e->getMessage());
        }

        return $result;
    }

    public function rejectRegistrationRequest(int $requestId, ?string $reason = null): \stdClass {
        $result = new \stdClass();
        $result->status = false;

        try {
            $ok = $this->pdo->prepare(
                "UPDATE registration_requests SET status = 'rejected', reviewed_at = NOW(), rejection_reason = ? WHERE id = ?"
            )->execute([$reason, $requestId]);

            if ($ok) {
                $result->status = true;
                $result->message = 'Registration request rejected.';
            } else {
                $result->message = 'Failed to reject request.';
            }
        } catch (\Throwable $e) {
            $result->message = 'Failed to reject request.';
        }

        return $result;
    }

    public function getPendingRegistrationRequests(string $role = ''): array {
        $params = [];
        $sql = "SELECT * FROM registration_requests WHERE status = 'pending'";

        if ($role !== '') {
            $sql .= " AND role = ?";
            $params[] = $role;
        }

        $sql .= " ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function createAppUser(int $authUserId, string $email, string $password, ?string $username, string $role, array $extra): void {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, password, email, role, first_name, last_name, phone_number, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
            ");
            $stmt->execute([
                $username ?? $email,
                $password,
                $email,
                $role,
                $extra['first_name'] ?? '',
                $extra['last_name'] ?? '',
                $extra['phone_number'] ?? null,
            ]);
        } catch (\PDOException $e) {
            error_log('AuthLibrary createAppUser error: ' . $e->getMessage());
        }
    }

    private function findAppUserByAuthId(int $authUserId): ?array {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
            $stmt->execute([$authUserId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ?: null;
        } catch (\PDOException $e) {
            return null;
        }
    }

    private function findAppUserByEmailAndRole(string $email, string $role): ?array {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ? LIMIT 1");
            $stmt->execute([$email, $role]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ?: null;
        } catch (\PDOException $e) {
            return null;
        }
    }

    private function syncAppSession(array $user): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION['user'] = (string)($user['username'] ?? '');
        $_SESSION['role'] = (string)($user['role'] ?? 'guest');
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['user_id'] = (int)($user['user_id'] ?? 0);
        $_SESSION['last_activity'] = time();
    }

    private function clearAppSession(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }
        unset($_SESSION['user'], $_SESSION['role'], $_SESSION['logged_in'], $_SESSION['login_time'], $_SESSION['user_id'], $_SESSION['last_activity']);
    }

    public function getAppUser(): ?array {
        if (!$this->isLoggedIn()) return null;
        $authUserId = $this->getUserId();
        if ($authUserId === null) return null;
        return $this->findAppUserByAuthId($authUserId);
    }
}
