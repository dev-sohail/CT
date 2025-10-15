<?php

declare(strict_types=1);

/**
 * Class Auth
 *
 * Comprehensive authentication system with device fingerprinting,
 * brute-force protection, and multi-factor authentication support.
 */
class Auth
{
    protected ?\PDO $db = null;
    protected ?Session $session = null;
    protected array $loginAttempts = [];
    protected int $maxAttempts = 5;
    protected int $lockoutTime = 900; // 15 minutes
    protected ?PasswordHasher $hasher = null;

    public function __construct(?object $db = null, ?Session $session = null)
    {
        if ($db instanceof \PDO) {
            $this->db = $db;
        } elseif (is_object($db) && method_exists($db, 'getConnection')) {
            $this->db = $db->getConnection();
        }

        $this->session = $session;
        $this->hasher = new PasswordHasher();
    }

    /**
     * Attempt user login with brute-force protection and device fingerprint check.
     */
    public function login(string $identifier, string $password, array $deviceData = []): bool
    {
        if ($this->isLockedOut($identifier)) {
            return false;
        }

        $user = $this->findUser($identifier);
        
        if (!$user || !$this->verifyPassword($password, $user['password'])) {
            $this->recordFailedAttempt($identifier);
            return false;
        }

        if (!empty($deviceData) && !$this->checkDeviceFingerprint($user['id'], $deviceData)) {
            // Log suspicious login attempt but don't fail
            $this->logSuspiciousAttempt($user['id'], $deviceData);
        }

        $this->resetAttempts($identifier);
        $this->createSession($user);
        
        return true;
    }

    /**
     * Find user by username or email.
     */
    protected function findUser(string $identifier): ?array
    {
        if (!$this->db) {
            return null;
        }

        $stmt = $this->db->prepare("
            SELECT id, username, email, password, role, status 
            FROM users 
            WHERE (username = :identifier OR email = :identifier) 
            AND status = 1
            LIMIT 1
        ");
        
        $stmt->execute(['identifier' => $identifier]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    /**
     * Verify a password against stored hash.
     */
    protected function verifyPassword(string $password, string $hash): bool
    {
        return $this->hasher->verify($password, $hash);
    }

    /**
     * Hash a password for storage.
     */
    public function hashPassword(string $password): string
    {
        return $this->hasher->hash($password);
    }

    /**
     * Create user session after successful login.
     */
    protected function createSession(array $user): void
    {
        if (!$this->session) {
            return;
        }

        $this->session->set('user_id', $user['id']);
        $this->session->set('username', $user['username']);
        $this->session->set('email', $user['email']);
        $this->session->set('role', $user['role']);
        $this->session->set('logged_in', true);
    }

    /**
     * Logout current user.
     */
    public function logout(): void
    {
        if ($this->session) {
            $this->session->destroy();
        }
    }

    /**
     * Check if user is logged in.
     */
    public function isLoggedIn(): bool
    {
        return $this->session && $this->session->get('logged_in', false) === true;
    }

    /**
     * Get current user ID.
     */
    public function getUserId(): ?int
    {
        return $this->session ? $this->session->get('user_id') : null;
    }

    /**
     * Get current user data.
     */
    public function getUser(): ?array
    {
        if (!$this->session || !$this->isLoggedIn()) {
            return null;
        }

        return [
            'id' => $this->session->get('user_id'),
            'username' => $this->session->get('username'),
            'email' => $this->session->get('email'),
            'role' => $this->session->get('role'),
        ];
    }

    /**
     * Record a failed login attempt.
     */
    protected function recordFailedAttempt(string $identifier): void
    {
        if (!isset($this->loginAttempts[$identifier])) {
            $this->loginAttempts[$identifier] = [];
        }
        
        $this->loginAttempts[$identifier][] = time();
    }

    /**
     * Check if the account is locked due to too many failed attempts.
     */
    protected function isLockedOut(string $identifier): bool
    {
        if (!isset($this->loginAttempts[$identifier])) {
            return false;
        }

        $attempts = array_filter(
            $this->loginAttempts[$identifier],
            fn($timestamp) => $timestamp > time() - $this->lockoutTime
        );

        $this->loginAttempts[$identifier] = array_values($attempts);
        
        return count($attempts) >= $this->maxAttempts;
    }

    /**
     * Reset login attempts after successful login.
     */
    protected function resetAttempts(string $identifier): void
    {
        unset($this->loginAttempts[$identifier]);
    }

    /**
     * Generate a device fingerprint.
     */
    public function generateDeviceFingerprint(array $deviceData): string
    {
        $normalized = [
            'user_agent' => $deviceData['user_agent'] ?? '',
            'screen_resolution' => $deviceData['screen_resolution'] ?? '',
            'timezone' => $deviceData['timezone'] ?? '',
            'language' => $deviceData['language'] ?? '',
        ];

        return hash('sha256', json_encode($normalized));
    }

    /**
     * Check if device fingerprint matches stored one.
     */
    protected function checkDeviceFingerprint(int $userId, array $deviceData): bool
    {
        $storedFingerprint = $this->getStoredDeviceFingerprint($userId);
        
        if (!$storedFingerprint) {
            // No fingerprint stored, save this one
            $this->saveDeviceFingerprint($userId, $deviceData);
            return true;
        }

        $currentFingerprint = $this->generateDeviceFingerprint($deviceData);
        return hash_equals($storedFingerprint, $currentFingerprint);
    }

    /**
     * Get stored device fingerprint from database.
     */
    protected function getStoredDeviceFingerprint(int $userId): ?string
    {
        if (!$this->db) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT device_fingerprint FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $userId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result['device_fingerprint'] ?? null;
    }

    /**
     * Save device fingerprint to database.
     */
    protected function saveDeviceFingerprint(int $userId, array $deviceData): void
    {
        if (!$this->db) {
            return;
        }

        $fingerprint = $this->generateDeviceFingerprint($deviceData);
        $stmt = $this->db->prepare("UPDATE users SET device_fingerprint = :fingerprint WHERE id = :id");
        $stmt->execute([
            'fingerprint' => $fingerprint,
            'id' => $userId
        ]);
    }

    /**
     * Log suspicious login attempt.
     */
    protected function logSuspiciousAttempt(int $userId, array $deviceData): void
    {
        if (!$this->db) {
            return;
        }

        $stmt = $this->db->prepare("
            INSERT INTO login_attempts (user_id, ip_address, user_agent, created_at, status)
            VALUES (:user_id, :ip, :user_agent, NOW(), 'suspicious')
        ");
        
        $stmt->execute([
            'user_id' => $userId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $deviceData['user_agent'] ?? 'unknown'
        ]);
    }

    /**
     * Set maximum login attempts.
     */
    public function setMaxAttempts(int $attempts): void
    {
        $this->maxAttempts = $attempts;
    }

    /**
     * Set lockout time in seconds.
     */
    public function setLockoutTime(int $seconds): void
    {
        $this->lockoutTime = $seconds;
    }
}
