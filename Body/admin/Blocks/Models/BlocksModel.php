<?php
declare(strict_types=1);
require_once ROOT . '/Brain/Core/Model.php';

class BlocksModel extends Model
{
    protected string $table = 'html_blocks';
    
    public function getAllBlocks(string $status = '', string $location = ''): array {
        try {
            $query = "SELECT * FROM {$this->table} WHERE 1=1";
            $params = [];
            
            if ($status) {
                $query .= " AND status = ?";
                $params[] = $status;
            }
            
            if ($location) {
                $query .= " AND location = ?";
                $params[] = $location;
            }
            
            $query .= " ORDER BY block_id DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Get blocks error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getBlockById(int $blockId): ?array {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE block_id = ?");
            $stmt->execute([$blockId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\PDOException $e) {
            error_log("Get block error: " . $e->getMessage());
            return null;
        }
    }
    
    public function createBlock(array $data): array {
        try {
            if (empty($data['block_name']) || empty($data['block_slug'])) {
                return ['success' => false, 'message' => 'Block name and slug are required'];
            }
            
            $checkStmt = $this->db->prepare("SELECT block_id FROM {$this->table} WHERE block_slug = ?");
            $checkStmt->execute([$data['block_slug']]);
            if ($checkStmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Block slug already exists'];
            }
            
            $stmt = $this->db->prepare("INSERT INTO {$this->table} (block_name, block_slug, content, location, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $success = $stmt->execute([$data['block_name'], $data['block_slug'], $data['content'], $data['location'] ?? 'global', $data['status'] ?? 'inactive']);
            
            return $success ? ['success' => true, 'message' => 'Block created successfully'] : ['success' => false, 'message' => 'Failed to create block'];
        } catch (\PDOException $e) {
            error_log("Create block error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    public function updateBlock(int $blockId, array $data): array {
        try {
            if (empty($data['block_name']) || empty($data['block_slug'])) {
                return ['success' => false, 'message' => 'Block name and slug are required'];
            }
            
            $checkStmt = $this->db->prepare("SELECT block_id FROM {$this->table} WHERE block_slug = ? AND block_id != ?");
            $checkStmt->execute([$data['block_slug'], $blockId]);
            if ($checkStmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Block slug already exists'];
            }
            
            $stmt = $this->db->prepare("UPDATE {$this->table} SET block_name = ?, block_slug = ?, content = ?, location = ?, status = ?, updated_at = NOW() WHERE block_id = ?");
            $success = $stmt->execute([$data['block_name'], $data['block_slug'], $data['content'], $data['location'] ?? 'global', $data['status'] ?? 'inactive', $blockId]);
            
            return $success ? ['success' => true, 'message' => 'Block updated successfully'] : ['success' => false, 'message' => 'Failed to update block'];
        } catch (\PDOException $e) {
            error_log("Update block error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    public function deleteBlock(int $blockId): array {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE block_id = ?");
            $success = $stmt->execute([$blockId]);
            return ($success && $stmt->rowCount() > 0) ? ['success' => true, 'message' => 'Block deleted successfully'] : ['success' => false, 'message' => 'Block not found'];
        } catch (\PDOException $e) {
            error_log("Delete block error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    public function toggleStatus(int $blockId): array {
        try {
            $stmt = $this->db->prepare("UPDATE {$this->table} SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END WHERE block_id = ?");
            $success = $stmt->execute([$blockId]);
            return $success ? ['success' => true, 'message' => 'Block status updated'] : ['success' => false, 'message' => 'Failed to update status'];
        } catch (\PDOException $e) {
            error_log("Toggle block status error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    public function getStats(): array {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active, SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive FROM {$this->table}");
            $stmt->execute();
            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: ['total' => 0, 'active' => 0, 'inactive' => 0];
        } catch (\PDOException $e) {
            error_log("Get stats error: " . $e->getMessage());
            return ['total' => 0, 'active' => 0, 'inactive' => 0];
        }
    }
}

