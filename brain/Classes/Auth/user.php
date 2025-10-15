<?php

declare(strict_types=1);

/**
 * Class User
 *
 * Represents a user entity with authentication and permission management.
 * Provides both ORM-style operations and authentication features.
 */
class User
{
    protected int $id = 0;
    protected string $username = '';
    protected string $email = '';
    protected string $password = '';
    protected string $role = 'user';
    protected int $status = 1;
    protected ?string $deviceFingerprint = null;
    protected array $permissions = [];
    protected array $metadata = [];

    protected static ?\PDO $pdo = null;

    /**
     * Constructor accepts either array data or Registry object.
     */
    public function __construct(mixed $data = [])
    {
        if (is_array($data)) {
            $this->fill($data);
        } elseif (is_object($data) && method_exists($data, 'get')) {
            // Registry object passed
            $db = $data->get('db');
            if ($db instanceof \PDO) {
                self::$pdo = $db;
            } elseif (method_exists($db, 'getConnection')) {
                self::$pdo = $db->getConnection();
            }
        }
    }

    /**
     * Set database instance.
     */
    public static function setDatabase(\PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    /**
     * Fill user data from array.
     */
    public function fill(array $data): void
    {
        $this->id = (int)($data['id'] ?? 0);
        $this->username = $data['username'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->password = $data['password'] ?? '';
        $this->role = $data['role'] ?? 'user';
        $this->status = (int)($data['status'] ?? 1);
        $this->deviceFingerprint = $data['device_fingerprint'] ?? null;
        $this->permissions = isset($data['permissions']) && is_array($data['permissions']) 
            ? $data['permissions'] 
            : [];
        $this->metadata = isset($data['metadata']) && is_array($data['metadata'])
            ? $data['metadata']
            : [];
    }

    /**
     * Find user by ID.
     */
    public static function findById(int $id): ?self
    {
        if (!self::$pdo) {
            return null;
        }

        $stmt = self::$pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $data ? new self($data) : null;
    }

    /**
     * Find user by email.
     */
    public static function findByEmail(string $email): ?self
    {
        if (!self::$pdo) {
            return null;
        }

        $stmt = self::$pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $data ? new self($data) : null;
    }

    /**
     * Find user by username.
     */
    public static function findByUsername(string $username): ?self
    {
        if (!self::$pdo) {
            return null;
        }

        $stmt = self::$pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => $username]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $data ? new self($data) : null;
    }

    /**
     * Authenticate user with email/username and password.
     */
    public static function authenticate(string $identifier, string $password): ?self
    {
        $user = self::findByEmail($identifier) ?? self::findByUsername($identifier);
        
        if ($user && password_verify($password, $user->password)) {
            return $user;
        }
        
        return null;
    }

    /**
     * Save user to database (insert or update).
     */
    public function save(): bool
    {
        if (!self::$pdo) {
            return false;
        }

        if ($this->id > 0) {
            return $this->update();
        }

        return $this->insert();
    }

    /**
     * Insert new user.
     */
    protected function insert(): bool
    {
        $stmt = self::$pdo->prepare("
            INSERT INTO users (username, email, password, role, status, device_fingerprint, created_at)
            VALUES (:username, :email, :password, :role, :status, :device_fingerprint, NOW())
        ");
        
        $result = $stmt->execute([
            'username' => $this->username,
            'email' => $this->email,
            'password' => $this->password,
            'role' => $this->role,
            'status' => $this->status,
            'device_fingerprint' => $this->deviceFingerprint
        ]);

        if ($result) {
            $this->id = (int)self::$pdo->lastInsertId();
        }

        return $result;
    }

    /**
     * Update existing user.
     */
    protected function update(): bool
    {
        $stmt = self::$pdo->prepare("
            UPDATE users 
            SET username = :username, 
                email = :email, 
                password = :password, 
                role = :role, 
                status = :status,
                device_fingerprint = :device_fingerprint,
                updated_at = NOW()
            WHERE id = :id
        ");
        
        return $stmt->execute([
            'username' => $this->username,
            'email' => $this->email,
            'password' => $this->password,
            'role' => $this->role,
            'status' => $this->status,
            'device_fingerprint' => $this->deviceFingerprint,
            'id' => $this->id
        ]);
    }

    /**
     * Delete user.
     */
    public function delete(): bool
    {
        if (!self::$pdo || $this->id <= 0) {
            return false;
        }

        $stmt = self::$pdo->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute(['id' => $this->id]);
    }

    /**
     * Check if user has permission.
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }

    /**
     * Add permission to user.
     */
    public function addPermission(string $permission): void
    {
        if (!$this->hasPermission($permission)) {
            $this->permissions[] = $permission;
        }
    }

    /**
     * Remove permission from user.
     */
    public function removePermission(string $permission): void
    {
        $this->permissions = array_filter($this->permissions, fn($p) => $p !== $permission);
        $this->permissions = array_values($this->permissions);
    }

    /**
     * Get user as array.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'role' => $this->role,
            'status' => $this->status,
            'permissions' => $this->permissions,
            'metadata' => $this->metadata
        ];
    }

    // Getters
    public function getId(): int { return $this->id; }
    public function getUsername(): string { return $this->username; }
    public function getEmail(): string { return $this->email; }
    public function getRole(): string { return $this->role; }
    public function getStatus(): int { return $this->status; }
    public function getPermissions(): array { return $this->permissions; }

    // Setters
    public function setUsername(string $username): void { $this->username = $username; }
    public function setEmail(string $email): void { $this->email = $email; }
    public function setPassword(string $password): void { 
        $this->password = password_hash($password, PASSWORD_DEFAULT); 
    }
    public function setRole(string $role): void { $this->role = $role; }
    public function setStatus(int $status): void { $this->status = $status; }
}
