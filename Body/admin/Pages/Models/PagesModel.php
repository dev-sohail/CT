<?php
declare(strict_types=1);
require_once ROOT . '/Brain/Core/Model.php';

class PagesModel extends Model
{
    protected string $table = 'pages';
    
    public function getAllPages(string $status = '', string $search = ''): array {
        try {
            $query = "SELECT * FROM {$this->table} WHERE 1=1";
            $params = [];
            
            if ($status) {
                $query .= " AND status = ?";
                $params[] = $status;
            }
            
            if ($search) {
                $query .= " AND (title LIKE ? OR slug LIKE ? OR content LIKE ?)";
                $searchTerm = "%{$search}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $query .= " ORDER BY page_id DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Get pages error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getPageById(int $pageId): ?array {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE page_id = ?");
            $stmt->execute([$pageId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\PDOException $e) {
            error_log("Get page error: " . $e->getMessage());
            return null;
        }
    }
    
    public function createPage(array $data): array {
        try {
            if (empty($data['title']) || empty($data['slug'])) {
                return ['success' => false, 'message' => 'Title and slug are required'];
            }
            
            $checkStmt = $this->db->prepare("SELECT page_id FROM {$this->table} WHERE slug = ?");
            $checkStmt->execute([$data['slug']]);
            if ($checkStmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Slug already exists'];
            }
            
            $stmt = $this->db->prepare("INSERT INTO {$this->table} (title, slug, content, meta_description, meta_keywords, status, author_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $success = $stmt->execute([$data['title'], $data['slug'], $data['content'], $data['meta_description'] ?? '', $data['meta_keywords'] ?? '', $data['status'] ?? 'draft', $data['author_id']]);
            
            return $success ? ['success' => true, 'message' => 'Page created successfully'] : ['success' => false, 'message' => 'Failed to create page'];
        } catch (\PDOException $e) {
            error_log("Create page error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    public function updatePage(int $pageId, array $data): array {
        try {
            if (empty($data['title']) || empty($data['slug'])) {
                return ['success' => false, 'message' => 'Title and slug are required'];
            }
            
            $checkStmt = $this->db->prepare("SELECT page_id FROM {$this->table} WHERE slug = ? AND page_id != ?");
            $checkStmt->execute([$data['slug'], $pageId]);
            if ($checkStmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Slug already exists'];
            }
            
            $stmt = $this->db->prepare("UPDATE {$this->table} SET title = ?, slug = ?, content = ?, meta_description = ?, meta_keywords = ?, status = ?, updated_at = NOW() WHERE page_id = ?");
            $success = $stmt->execute([$data['title'], $data['slug'], $data['content'], $data['meta_description'] ?? '', $data['meta_keywords'] ?? '', $data['status'] ?? 'draft', $pageId]);
            
            return $success ? ['success' => true, 'message' => 'Page updated successfully'] : ['success' => false, 'message' => 'Failed to update page'];
        } catch (\PDOException $e) {
            error_log("Update page error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    public function deletePage(int $pageId): array {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE page_id = ?");
            $success = $stmt->execute([$pageId]);
            return ($success && $stmt->rowCount() > 0) ? ['success' => true, 'message' => 'Page deleted successfully'] : ['success' => false, 'message' => 'Page not found'];
        } catch (\PDOException $e) {
            error_log("Delete page error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    public function toggleStatus(int $pageId): array {
        try {
            $stmt = $this->db->prepare("UPDATE {$this->table} SET status = CASE WHEN status = 'published' THEN 'draft' ELSE 'published' END WHERE page_id = ?");
            $success = $stmt->execute([$pageId]);
            return $success ? ['success' => true, 'message' => 'Page status updated'] : ['success' => false, 'message' => 'Failed to update status'];
        } catch (\PDOException $e) {
            error_log("Toggle page status error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    public function getStats(): array {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published, SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft FROM {$this->table}");
            $stmt->execute();
            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: ['total' => 0, 'published' => 0, 'draft' => 0];
        } catch (\PDOException $e) {
            error_log("Get stats error: " . $e->getMessage());
            return ['total' => 0, 'published' => 0, 'draft' => 0];
        }
    }
}

