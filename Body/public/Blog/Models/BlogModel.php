<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Model.php';

/**
 * Public Blog Model
 * 
 * Handles public blog data operations
 */
class BlogModel extends Model
{
    protected string $table = 'blogs';
    protected string $primaryKey = 'id';

    /**
     * Find published blogs with search functionality
     * 
     * @param array $conditions Search conditions
     * @param int $limit Query limit
     * @param int $offset Query offset
     * @return array Blogs
     */
    public function findAll(array $conditions = [], int $limit = 0, int $offset = 0): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE status = 'published'";
        $params = [];
        $whereClause = [];

        // Handle search condition
        if (isset($conditions['search']) && !empty($conditions['search'])) {
            $whereClause[] = "(title LIKE ? OR content LIKE ? OR excerpt LIKE ?)";
            $searchTerm = '%' . $conditions['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            unset($conditions['search']);
        }

        // Handle tag condition
        if (isset($conditions['tag']) && !empty($conditions['tag'])) {
            $whereClause[] = "tags LIKE ?";
            $params[] = '%' . $conditions['tag'] . '%';
            unset($conditions['tag']);
        }

        // Handle other conditions
        foreach ($conditions as $field => $value) {
            if ($field !== 'search' && $field !== 'tag') {
                $whereClause[] = "{$field} = ?";
                $params[] = $value;
            }
        }

        if (!empty($whereClause)) {
            $sql .= " AND " . implode(' AND ', $whereClause);
        }

        $sql .= " ORDER BY created_at DESC";

        if ($limit > 0) {
            $sql .= " LIMIT {$limit}";
            if ($offset > 0) {
                $sql .= " OFFSET {$offset}";
            }
        }

        $result = $this->query($sql, $params);

        if (is_object($result) && method_exists($result, 'rows')) {
            return $result->rows;
        }

        return [];
    }

    /**
     * Count published blogs with search functionality
     * 
     * @param array $conditions Search conditions
     * @return int Count
     */
    public function count(array $conditions = []): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE status = 'published'";
        $params = [];
        $whereClause = [];

        // Handle search condition
        if (isset($conditions['search']) && !empty($conditions['search'])) {
            $whereClause[] = "(title LIKE ? OR content LIKE ? OR excerpt LIKE ?)";
            $searchTerm = '%' . $conditions['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            unset($conditions['search']);
        }

        // Handle tag condition
        if (isset($conditions['tag']) && !empty($conditions['tag'])) {
            $whereClause[] = "tags LIKE ?";
            $params[] = '%' . $conditions['tag'] . '%';
            unset($conditions['tag']);
        }

        // Handle other conditions
        foreach ($conditions as $field => $value) {
            if ($field !== 'search' && $field !== 'tag') {
                $whereClause[] = "{$field} = ?";
                $params[] = $value;
            }
        }

        if (!empty($whereClause)) {
            $sql .= " AND " . implode(' AND ', $whereClause);
        }

        $result = $this->query($sql, $params);

        if (is_object($result) && method_exists($result, 'row')) {
            return (int) $result->row['count'];
        }

        return 0;
    }

    /**
     * Find blog by slug
     * 
     * @param string $slug Blog slug
     * @return array|null Blog data or null
     */
    public function findBySlug(string $slug): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE slug = ? AND status = 'published'";
        $result = $this->query($sql, [$slug]);

        if (is_object($result) && method_exists($result, 'row')) {
            return $result->row;
        }

        return null;
    }

    /**
     * Find related blogs
     * 
     * @param int $blogId Current blog ID
     * @param int $limit Number of related blogs
     * @return array Related blogs
     */
    public function findRelated(int $blogId, int $limit = 5): array
    {
        // Get current blog tags
        $blog = $this->findById($blogId);
        if (!$blog || empty($blog['tags'])) {
            return [];
        }

        $tags = explode(',', $blog['tags']);
        $tagConditions = [];
        $params = [];

        foreach ($tags as $tag) {
            $tagConditions[] = "tags LIKE ?";
            $params[] = '%' . trim($tag) . '%';
        }

        $sql = "SELECT * FROM {$this->table} 
                WHERE id != ? AND status = 'published' AND (" . implode(' OR ', $tagConditions) . ")
                ORDER BY created_at DESC LIMIT {$limit}";

        array_unshift($params, $blogId);

        $result = $this->query($sql, $params);

        if (is_object($result) && method_exists($result, 'rows')) {
            return $result->rows;
        }

        return [];
    }

    /**
     * Find published blogs
     * 
     * @param int $limit Query limit
     * @param int $offset Query offset
     * @return array Published blogs
     */
    public function findPublished(int $limit = 0, int $offset = 0): array
    {
        return $this->findAll(['status' => 'published'], $limit, $offset);
    }

    /**
     * Get recent blogs
     * 
     * @param int $limit Number of recent blogs
     * @return array Recent blogs
     */
    public function getRecent(int $limit = 5): array
    {
        return $this->findPublished($limit);
    }

    /**
     * Get popular blogs (by view count if available)
     * 
     * @param int $limit Number of popular blogs
     * @return array Popular blogs
     */
    public function getPopular(int $limit = 5): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE status = 'published' 
                ORDER BY view_count DESC, created_at DESC 
                LIMIT {$limit}";

        $result = $this->query($sql);

        if (is_object($result) && method_exists($result, 'rows')) {
            return $result->rows;
        }

        return [];
    }

    /**
     * Increment view count
     * 
     * @param int $id Blog ID
     */
    public function incrementViewCount(int $id): void
    {
        $sql = "UPDATE {$this->table} SET view_count = view_count + 1 WHERE id = ?";
        $this->query($sql, [$id]);
    }
}
