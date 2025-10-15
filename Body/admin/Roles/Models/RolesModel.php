<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Model.php';

/**
 * Roles Model
 * 
 * Handle role database operations
 */
class RolesModel extends Model
{
    protected string $table = 'roles';
    
    /**
     * Get all roles
     */
    public function getAllRoles(): array
    {
        try {
            $query = "SELECT * FROM {$this->table} ORDER BY role_id ASC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Get roles error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get role by ID
     */
    public function getRoleById(int $roleId): ?array
    {
        try {
            $query = "SELECT * FROM {$this->table} WHERE role_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$roleId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\PDOException $e) {
            error_log("Get role error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create new role
     */
    public function createRole(array $data): array
    {
        try {
            // Validate required fields
            if (empty($data['role_name']) || empty($data['role_slug'])) {
                return [
                    'success' => false,
                    'message' => 'Role name and slug are required'
                ];
            }
            
            // Check if slug already exists
            $checkQuery = "SELECT role_id FROM {$this->table} WHERE role_slug = ?";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([$data['role_slug']]);
            
            if ($checkStmt->rowCount() > 0) {
                return [
                    'success' => false,
                    'message' => 'Role slug already exists'
                ];
            }
            
            // Insert new role
            $query = "INSERT INTO {$this->table} 
                     (role_name, role_slug, display_name, icon, color, is_active, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($query);
            $success = $stmt->execute([
                $data['role_name'],
                $data['role_slug'],
                $data['display_name'] ?? $data['role_name'],
                $data['icon'] ?? '👤',
                $data['color'] ?? '#667eea',
                $data['is_active'] ?? 1
            ]);
            
            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Role created successfully'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to create role'
            ];
            
        } catch (\PDOException $e) {
            error_log("Create role error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error occurred'
            ];
        }
    }
    
    /**
     * Update role
     */
    public function updateRole(int $roleId, array $data): array
    {
        try {
            // Validate required fields
            if (empty($data['role_name']) || empty($data['role_slug'])) {
                return [
                    'success' => false,
                    'message' => 'Role name and slug are required'
                ];
            }
            
            // Check if slug exists for other roles
            $checkQuery = "SELECT role_id FROM {$this->table} WHERE role_slug = ? AND role_id != ?";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([$data['role_slug'], $roleId]);
            
            if ($checkStmt->rowCount() > 0) {
                return [
                    'success' => false,
                    'message' => 'Role slug already exists'
                ];
            }
            
            // Update role
            $query = "UPDATE {$this->table} 
                     SET role_name = ?, role_slug = ?, display_name = ?, 
                         icon = ?, color = ?, is_active = ?
                     WHERE role_id = ?";
            
            $stmt = $this->db->prepare($query);
            $success = $stmt->execute([
                $data['role_name'],
                $data['role_slug'],
                $data['display_name'] ?? $data['role_name'],
                $data['icon'] ?? '👤',
                $data['color'] ?? '#667eea',
                $data['is_active'] ?? 1,
                $roleId
            ]);
            
            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Role updated successfully'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to update role'
            ];
            
        } catch (\PDOException $e) {
            error_log("Update role error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error occurred'
            ];
        }
    }
    
    /**
     * Delete role
     */
    public function deleteRole(int $roleId): array
    {
        try {
            // Check if role is assigned to any users
            $checkQuery = "SELECT COUNT(*) as count FROM user_info WHERE role = (SELECT role_slug FROM {$this->table} WHERE role_id = ?)";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([$roleId]);
            $result = $checkStmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                return [
                    'success' => false,
                    'message' => 'Cannot delete role - it is assigned to ' . $result['count'] . ' user(s)'
                ];
            }
            
            // Delete role
            $query = "DELETE FROM {$this->table} WHERE role_id = ?";
            $stmt = $this->db->prepare($query);
            $success = $stmt->execute([$roleId]);
            
            if ($success && $stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Role deleted successfully'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Role not found or already deleted'
            ];
            
        } catch (\PDOException $e) {
            error_log("Delete role error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error occurred'
            ];
        }
    }
    
    /**
     * Toggle role status
     */
    public function toggleRoleStatus(int $roleId): array
    {
        try {
            $query = "UPDATE {$this->table} SET is_active = NOT is_active WHERE role_id = ?";
            $stmt = $this->db->prepare($query);
            $success = $stmt->execute([$roleId]);
            
            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Role status updated'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to update role status'
            ];
            
        } catch (\PDOException $e) {
            error_log("Toggle role status error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error occurred'
            ];
        }
    }
    
    /**
     * Get role statistics
     */
    public function getRoleStats(int $roleId): array
    {
        try {
            $query = "SELECT 
                        r.role_name,
                        r.role_slug,
                        COUNT(u.user_id) as user_count,
                        r.is_active
                      FROM {$this->table} r
                      LEFT JOIN user_info u ON u.role = r.role_slug
                      WHERE r.role_id = ?
                      GROUP BY r.role_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$roleId]);
            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("Get role stats error: " . $e->getMessage());
            return [];
        }
    }
}

