<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Model.php';

/**
 * Blog Model
 * 
 * Handles blog data operations
 */
class BlogModel extends Model
{
    protected string $table = 'blogs';
    protected string $primaryKey = 'id';

    /**
     * Find blogs with search functionality
     * 
     * @param array $conditions Search conditions
     * @param int $limit Query limit
     * @param int $offset Query offset
     * @return array Blogs
     */
    public function findAll(array $conditions = [], int $limit = 0, int $offset = 0): array
    {
        $sql = "SELECT * FROM {$this->table}";
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

        // Handle other conditions
        foreach ($conditions as $field => $value) {
            if ($field !== 'search') {
                $whereClause[] = "{$field} = ?";
                $params[] = $value;
            }
        }

        if (!empty($whereClause)) {
            $sql .= " WHERE " . implode(' AND ', $whereClause);
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
     * Count blogs with search functionality
     * 
     * @param array $conditions Search conditions
     * @return int Count
     */
    public function count(array $conditions = []): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
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

        // Handle other conditions
        foreach ($conditions as $field => $value) {
            if ($field !== 'search') {
                $whereClause[] = "{$field} = ?";
                $params[] = $value;
            }
        }

        if (!empty($whereClause)) {
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }

        $result = $this->query($sql, $params);

        if (is_object($result) && method_exists($result, 'row')) {
            return (int) $result->row['count'];
        }

        return 0;
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
     * Get blog statistics
     * 
     * @return array Statistics
     */
    public function getStatistics(): array
    {
        $stats = [
            'total' => $this->count(),
            'published' => $this->count(['status' => 'published']),
            'draft' => $this->count(['status' => 'draft']),
            'archived' => $this->count(['status' => 'archived'])
        ];

        // Get monthly statistics
        $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
                FROM {$this->table} 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY month 
                ORDER BY month DESC";

        $result = $this->query($sql);
        $monthlyStats = [];

        if (is_object($result) && method_exists($result, 'rows')) {
            foreach ($result->rows as $row) {
                $monthlyStats[$row['month']] = (int) $row['count'];
            }
        }

        $stats['monthly'] = $monthlyStats;

        return $stats;
    }

    /**
     * Insert blog with slug generation
     * 
     * @param array $data Blog data
     * @return int|string New blog ID
     */
    public function insert(array $data): int|string
    {
        // Generate slug if not provided
        if (empty($data['slug']) && !empty($data['title'])) {
            $data['slug'] = $this->generateSlug($data['title']);
        }

        return parent::insert($data);
    }

    /**
     * Update blog with slug generation
     * 
     * @param int|string $id Blog ID
     * @param array $data Blog data
     * @return bool Success status
     */
    public function update(int|string $id, array $data): bool
    {
        // Generate slug if title is updated and slug is empty
        if (!empty($data['title']) && empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['title']);
        }

        return parent::update($id, $data);
    }

    /**
     * Generate unique slug from title
     * 
     * @param string $title Blog title
     * @return string Unique slug
     */
    private function generateSlug(string $title): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $slug = trim($slug, '-');

        $originalSlug = $slug;
        $counter = 1;

        // Check if slug exists and make it unique
        while ($this->slugExists($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if slug exists
     * 
     * @param string $slug Slug to check
     * @return bool True if exists
     */
    private function slugExists(string $slug): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE slug = ?";
        $result = $this->query($sql, [$slug]);

        if (is_object($result) && method_exists($result, 'row')) {
            return (int) $result->row['count'] > 0;
        }

        return false;
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
