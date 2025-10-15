<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Model.php';

/**
 * Users Model
 * 
 * Handle user database operations
 */
class UsersModel extends Model
{
    protected string $table = 'user_info';
    
    /**
     * Get all users with optional filters
     */
    public function getAllUsers(string $roleFilter = '', string $statusFilter = '', string $searchQuery = ''): array
    {
        try {
            $query = "SELECT * FROM {$this->table} WHERE 1=1";
            $params = [];
            
            if (!empty($roleFilter)) {
                $query .= " AND role = ?";
                $params[] = $roleFilter;
            }
            
            if (!empty($statusFilter)) {
                $query .= " AND status = ?";
                $params[] = $statusFilter;
            }
            
            if (!empty($searchQuery)) {
                $query .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR username LIKE ?)";
                $searchTerm = "%{$searchQuery}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $query .= " ORDER BY user_id DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Get users error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get user by ID
     */
    public function getUserById(int $userId): ?array
    {
        try {
            $query = "SELECT * FROM {$this->table} WHERE user_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\PDOException $e) {
            error_log("Get user error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create new user
     */
    public function createUser(array $data): array
    {
        try {
            // Validate required fields
            if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email']) || empty($data['password']) || empty($data['role'])) {
                return [
                    'success' => false,
                    'message' => 'All required fields must be filled'
                ];
            }
            
            // Validate email
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'message' => 'Invalid email format'
                ];
            }
            
            // Check if email already exists for this role
            $checkQuery = "SELECT user_id FROM {$this->table} WHERE email = ? AND role = ?";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([$data['email'], $data['role']]);
            
            if ($checkStmt->rowCount() > 0) {
                return [
                    'success' => false,
                    'message' => 'Email already exists for this role'
                ];
            }
            
            // Generate username
            $username = $this->generateUsername($data['first_name'], $data['last_name']);
            
            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Insert user
            $query = "INSERT INTO {$this->table} 
                     (first_name, last_name, username, email, password, role, phone_number, status, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($query);
            $success = $stmt->execute([
                $data['first_name'],
                $data['last_name'],
                $username,
                $data['email'],
                $hashedPassword,
                $data['role'],
                $data['phone_number'] ?? null,
                $data['status'] ?? 'active'
            ]);
            
            if ($success) {
                return [
                    'success' => true,
                    'message' => "User created successfully! Username: {$username}"
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to create user'
            ];
            
        } catch (\PDOException $e) {
            error_log("Create user error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error occurred'
            ];
        }
    }
    
    /**
     * Update user
     */
    public function updateUser(int $userId, array $data): array
    {
        try {
            // Validate required fields
            if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email']) || empty($data['role'])) {
                return [
                    'success' => false,
                    'message' => 'All required fields must be filled'
                ];
            }
            
            // Validate email
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'message' => 'Invalid email format'
                ];
            }
            
            // Check if email exists for other users with same role
            $checkQuery = "SELECT user_id FROM {$this->table} WHERE email = ? AND role = ? AND user_id != ?";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([$data['email'], $data['role'], $userId]);
            
            if ($checkStmt->rowCount() > 0) {
                return [
                    'success' => false,
                    'message' => 'Email already exists for another user with this role'
                ];
            }
            
            // Build update query
            if (isset($data['password']) && !empty($data['password'])) {
                // Update with password
                $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
                $query = "UPDATE {$this->table} 
                         SET first_name = ?, last_name = ?, email = ?, password = ?, 
                             role = ?, phone_number = ?, status = ?, updated_at = NOW()
                         WHERE user_id = ?";
                $params = [
                    $data['first_name'],
                    $data['last_name'],
                    $data['email'],
                    $hashedPassword,
                    $data['role'],
                    $data['phone_number'] ?? null,
                    $data['status'] ?? 'active',
                    $userId
                ];
            } else {
                // Update without password
                $query = "UPDATE {$this->table} 
                         SET first_name = ?, last_name = ?, email = ?, 
                             role = ?, phone_number = ?, status = ?, updated_at = NOW()
                         WHERE user_id = ?";
                $params = [
                    $data['first_name'],
                    $data['last_name'],
                    $data['email'],
                    $data['role'],
                    $data['phone_number'] ?? null,
                    $data['status'] ?? 'active',
                    $userId
                ];
            }
            
            $stmt = $this->db->prepare($query);
            $success = $stmt->execute($params);
            
            if ($success) {
                return [
                    'success' => true,
                    'message' => 'User updated successfully'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to update user'
            ];
            
        } catch (\PDOException $e) {
            error_log("Update user error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error occurred'
            ];
        }
    }
    
    /**
     * Delete user
     */
    public function deleteUser(int $userId): array
    {
        try {
            $query = "DELETE FROM {$this->table} WHERE user_id = ?";
            $stmt = $this->db->prepare($query);
            $success = $stmt->execute([$userId]);
            
            if ($success && $stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'User deleted successfully'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'User not found or already deleted'
            ];
            
        } catch (\PDOException $e) {
            error_log("Delete user error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error occurred'
            ];
        }
    }
    
    /**
     * Toggle user status
     */
    public function toggleUserStatus(int $userId): array
    {
        try {
            // Toggle between active and inactive
            $query = "UPDATE {$this->table} 
                     SET status = CASE 
                         WHEN status = 'active' THEN 'inactive'
                         WHEN status = 'inactive' THEN 'active'
                         ELSE 'active'
                     END
                     WHERE user_id = ?";
            $stmt = $this->db->prepare($query);
            $success = $stmt->execute([$userId]);
            
            if ($success) {
                return [
                    'success' => true,
                    'message' => 'User status updated'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to update user status'
            ];
            
        } catch (\PDOException $e) {
            error_log("Toggle user status error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error occurred'
            ];
        }
    }
    
    /**
     * Get user statistics
     */
    public function getUserStats(): array
    {
        try {
            $query = "SELECT 
                        COUNT(*) as total_users,
                        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
                        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_users,
                        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_count,
                        SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END) as student_count,
                        SUM(CASE WHEN role = 'teacher' THEN 1 ELSE 0 END) as teacher_count
                      FROM {$this->table}";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("Get user stats error: " . $e->getMessage());
            return [
                'total_users' => 0,
                'active_users' => 0,
                'inactive_users' => 0,
                'admin_count' => 0,
                'student_count' => 0,
                'teacher_count' => 0
            ];
        }
    }
    
    /**
     * Generate unique username
     */
    private function generateUsername(string $firstName, string $lastName): string
    {
        $baseUsername = strtolower(preg_replace('/\s+/', '', $firstName)) . '.' . 
                        strtolower(preg_replace('/\s+/', '', $lastName));
        
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
}

