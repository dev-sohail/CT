<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Model.php';

/**
 * Auth Model
 * 
 * Handles authentication data operations
 */
class AuthModel extends Model
{
    protected string $table = 'user_info';
    protected string $rolesTable = 'roles';
    
    /**
     * Get all active roles from database
     * 
     * @return array List of active roles
     */
    public function getRoles(): array
    {
        try {
            // Gracefully handle missing database connection
            if (!$this->hasDatabase() || !($this->db instanceof \PDO)) {
                return $this->getDefaultRoles();
            }
            $query = "SELECT role_slug, display_name, icon, color FROM {$this->rolesTable} WHERE is_active = 1 ORDER BY role_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $roles = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Fallback to default roles if table doesn't exist or is empty
            if (empty($roles)) {
                return $this->getDefaultRoles();
            }
            
            return $roles;
        } catch (\Throwable $e) {
            error_log("Get roles error: " . $e->getMessage());
            return $this->getDefaultRoles();
        }
    }
    
    /**
     * Get default roles (fallback)
     * 
     * @return array Default roles
     */
    private function getDefaultRoles(): array
    {
        return [
            ['role_slug' => 'student', 'display_name' => 'Student Login', 'icon' => '👨‍🎓', 'color' => '#667eea'],
            ['role_slug' => 'teacher', 'display_name' => 'Teacher Login', 'icon' => '👨‍🏫', 'color' => '#764ba2'],
            ['role_slug' => 'admin', 'display_name' => 'Admin Login', 'icon' => '👨‍💼', 'color' => '#dc3545'],
            ['role_slug' => 'parent', 'display_name' => 'Parent Login', 'icon' => '👪', 'color' => '#28a745'],
            ['role_slug' => 'staff', 'display_name' => 'Staff Login', 'icon' => '👔', 'color' => '#ffc107'],
        ];
    }
    
    /**
     * Authenticate user
     * 
     * @param string $username Username or email
     * @param string $password Password
     * @param string $role User role
     * @return array Result with success status, user data, and message
     */
    public function authenticate(string $username, string $password, string $role): array
    {
        try {
            // Guard against missing database connection
            if (!$this->hasDatabase() || !($this->db instanceof \PDO)) {
                return [
                    'success' => false,
                    'message' => 'Database not available. Please configure the database connection.'
                ];
            }
            // Check if username is an email
            $isEmail = filter_var($username, FILTER_VALIDATE_EMAIL);
            
            // Query user by username/email AND role
            if ($isEmail) {
                $query = "SELECT user_id, username, email, first_name, last_name, password, role, status, last_login 
                         FROM {$this->table} 
                         WHERE email = ? AND role = ?";
            } else {
                $query = "SELECT user_id, username, email, first_name, last_name, password, role, status, last_login 
                         FROM {$this->table} 
                         WHERE username = ? AND role = ?";
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$username, $role]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Invalid ' . ($isEmail ? 'email' : 'username') . ' or role.'
                ];
            }
            
            // Check if account is active
            if ($user['status'] !== 'active') {
                return [
                    'success' => false,
                    'message' => 'Your account is ' . $user['status'] . '. Please contact support.'
                ];
            }
            
            // Verify password (support both plain and hashed)
            $storedPassword = $user['password'];
            $isValid = false;
            
            if (password_get_info($storedPassword)['algo'] !== null) {
                // Hashed password
                $isValid = password_verify($password, $storedPassword);
            } else {
                // Plain text password (for backward compatibility)
                $isValid = $password === $storedPassword;
            }
            
            if ($isValid) {
                // Update last login
                $this->updateLastLogin($user['user_id']);
                
                // Remove password from returned data
                unset($user['password']);
                
                return [
                    'success' => true,
                    'user' => $user,
                    'user_id' => $user['user_id'],
                    'message' => 'Login successful!'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Invalid password.'
                ];
            }
        } catch (\Throwable $e) {
            error_log("Authentication error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred. Please try again.'
            ];
        }
    }
    
    /**
     * Update last login timestamp
     * 
     * @param int $userId User ID
     * @return void
     */
    private function updateLastLogin(int $userId): void
    {
        try {
            $query = "UPDATE {$this->table} SET last_login = NOW() WHERE user_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId]);
        } catch (\PDOException $e) {
            error_log("Update last login error: " . $e->getMessage());
        }
    }
    
    /**
     * Register new user
     * 
     * @param array $data User data
     * @return array Result with success status and message
     */
    public function register(array $data): array
    {
        try {
            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'message' => 'Invalid email format.'
                ];
            }
            
            // Validate phone if provided
            if (!empty($data['phone']) && !preg_match('/^\+?[0-9]{7,15}$/', $data['phone'])) {
                return [
                    'success' => false,
                    'message' => 'Invalid phone number format.'
                ];
            }
            
            // Check if email already exists for this role
            $checkQuery = "SELECT user_id FROM {$this->table} WHERE email = ? AND role = ?";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([$data['email'], $data['role']]);
            
            if ($checkStmt->rowCount() > 0) {
                return [
                    'success' => false,
                    'message' => 'You have already registered with this email.'
                ];
            }
            
            // Generate unique username
            $username = $this->generateUsername($data['first_name'], $data['last_name']);
            
            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Insert new user
            $insertQuery = "INSERT INTO {$this->table} 
                           (first_name, last_name, username, email, password, role, phone_number, created_at) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $insertStmt = $this->db->prepare($insertQuery);
            $success = $insertStmt->execute([
                $data['first_name'],
                $data['last_name'],
                $username,
                $data['email'],
                $hashedPassword,
                $data['role'],
                $data['phone'] ?? null
            ]);
            
            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Registration successful! Your username is: ' . $username
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Registration failed. Please try again.'
                ];
            }
        } catch (\PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred during registration.'
            ];
        }
    }
    
    /**
     * Reset password
     * 
     * @param string $email User email
     * @param string $role User role
     * @return array Result with success status and message
     */
    public function resetPassword(string $email, string $role): array
    {
        try {
            // Check if user exists
            $query = "SELECT user_id, username FROM {$this->table} WHERE email = ? AND role = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$email, $role]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'No account found with that email and role.'
                ];
            }
            
            // Generate temporary password
            $tempPassword = $this->generateTempPassword();
            $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
            
            // Update password
            $updateQuery = "UPDATE {$this->table} SET password = ?, updated_at = NOW() WHERE user_id = ?";
            $updateStmt = $this->db->prepare($updateQuery);
            $success = $updateStmt->execute([$hashedPassword, $user['user_id']]);
            
            if ($success) {
                // In production, send email with temp password
                // For now, just log it
                error_log("Temporary password for {$user['username']}: {$tempPassword}");
                
                return [
                    'success' => true,
                    'message' => 'Password reset successful! Check your email for the temporary password. (Demo: Check logs)'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to reset password.'
                ];
            }
        } catch (\PDOException $e) {
            error_log("Password reset error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred. Please try again.'
            ];
        }
    }
    
    /**
     * Generate unique username
     * 
     * @param string $firstName First name
     * @param string $lastName Last name
     * @return string Unique username
     */
    private function generateUsername(string $firstName, string $lastName): string
    {
        $baseUsername = strtolower(preg_replace('/\s+/', '', $firstName)) . '.' . 
                        strtolower(preg_replace('/\s+/', '', $lastName));
        
        // Check for existing usernames
        $query = "SELECT username FROM {$this->table} WHERE username LIKE CONCAT(?, '%')";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$baseUsername]);
        $existingUsernames = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        
        $username = $baseUsername;
        $suffix = 1;
        
        while (in_array($username, $existingUsernames)) {
            $username = $baseUsername . $suffix;
            $suffix++;
        }
        
        return $username;
    }
    
    /**
     * Generate temporary password
     * 
     * @return string Temporary password
     */
    private function generateTempPassword(): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
        $length = 12;
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $password;
    }
}

