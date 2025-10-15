<?php

declare(strict_types=1);

/**
 * Class AuditTrail
 *
 * Comprehensive audit logging system for tracking user actions and system events.
 */
class AuditTrail
{
    protected ?\PDO $db = null;
    protected string $table = 'audit_trail';
    protected bool $enabled = true;

    public function __construct(?object $db = null)
    {
        if ($db instanceof \PDO) {
            $this->db = $db;
        } elseif (is_object($db) && method_exists($db, 'getConnection')) {
            $this->db = $db->getConnection();
        }
    }

    /**
     * Log an event to the audit trail.
     */
    public function log(string $action, string $entity, int|string $entityId, array $data = [], ?int $userId = null): bool
    {
        if (!$this->enabled || !$this->db) {
            return false;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO {$this->table} 
                (user_id, action, entity, entity_id, data, ip_address, user_agent, created_at)
                VALUES (:user_id, :action, :entity, :entity_id, :data, :ip, :user_agent, NOW())
            ");

            return $stmt->execute([
                'user_id' => $userId,
                'action' => $action,
                'entity' => $entity,
                'entity_id' => (string)$entityId,
                'data' => json_encode($data),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        } catch (\PDOException $e) {
            error_log("Audit Trail Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log a create action.
     */
    public function logCreate(string $entity, int|string $entityId, array $data = [], ?int $userId = null): bool
    {
        return $this->log('create', $entity, $entityId, $data, $userId);
    }

    /**
     * Log an update action.
     */
    public function logUpdate(string $entity, int|string $entityId, array $data = [], ?int $userId = null): bool
    {
        return $this->log('update', $entity, $entityId, $data, $userId);
    }

    /**
     * Log a delete action.
     */
    public function logDelete(string $entity, int|string $entityId, array $data = [], ?int $userId = null): bool
    {
        return $this->log('delete', $entity, $entityId, $data, $userId);
    }

    /**
     * Log a view action.
     */
    public function logView(string $entity, int|string $entityId, array $data = [], ?int $userId = null): bool
    {
        return $this->log('view', $entity, $entityId, $data, $userId);
    }

    /**
     * Log a login action.
     */
    public function logLogin(int $userId, bool $success = true): bool
    {
        return $this->log(
            $success ? 'login_success' : 'login_failed',
            'user',
            $userId,
            ['success' => $success],
            $userId
        );
    }

    /**
     * Log a logout action.
     */
    public function logLogout(int $userId): bool
    {
        return $this->log('logout', 'user', $userId, [], $userId);
    }

    /**
     * Get audit logs with optional filters.
     */
    public function getLogs(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        if (!$this->db) {
            return [];
        }

        $where = [];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = :user_id';
            $params['user_id'] = $filters['user_id'];
        }

        if (!empty($filters['action'])) {
            $where[] = 'action = :action';
            $params['action'] = $filters['action'];
        }

        if (!empty($filters['entity'])) {
            $where[] = 'entity = :entity';
            $params['entity'] = $filters['entity'];
        }

        if (!empty($filters['entity_id'])) {
            $where[] = 'entity_id = :entity_id';
            $params['entity_id'] = $filters['entity_id'];
        }

        if (!empty($filters['from_date'])) {
            $where[] = 'created_at >= :from_date';
            $params['from_date'] = $filters['from_date'];
        }

        if (!empty($filters['to_date'])) {
            $where[] = 'created_at <= :to_date';
            $params['to_date'] = $filters['to_date'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT * FROM {$this->table} {$whereClause} ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Audit Trail Query Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get audit logs for a specific entity.
     */
    public function getEntityHistory(string $entity, int|string $entityId, int $limit = 50): array
    {
        return $this->getLogs([
            'entity' => $entity,
            'entity_id' => $entityId
        ], $limit);
    }

    /**
     * Get audit logs for a specific user.
     */
    public function getUserActivity(int $userId, int $limit = 100): array
    {
        return $this->getLogs(['user_id' => $userId], $limit);
    }

    /**
     * Count audit logs with optional filters.
     */
    public function count(array $filters = []): int
    {
        if (!$this->db) {
            return 0;
        }

        $where = [];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = :user_id';
            $params['user_id'] = $filters['user_id'];
        }

        if (!empty($filters['action'])) {
            $where[] = 'action = :action';
            $params['action'] = $filters['action'];
        }

        if (!empty($filters['entity'])) {
            $where[] = 'entity = :entity';
            $params['entity'] = $filters['entity'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT COUNT(*) as count FROM {$this->table} {$whereClause}";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return (int)($result['count'] ?? 0);
        } catch (\PDOException $e) {
            error_log("Audit Trail Count Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Enable audit logging.
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disable audit logging.
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Set custom table name.
     */
    public function setTable(string $table): void
    {
        $this->table = $table;
    }

    /**
     * Purge old audit logs.
     */
    public function purgeOldLogs(int $daysToKeep = 90): int
    {
        if (!$this->db) {
            return 0;
        }

        try {
            $stmt = $this->db->prepare("
                DELETE FROM {$this->table} 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)
            ");
            
            $stmt->execute(['days' => $daysToKeep]);
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            error_log("Audit Trail Purge Error: " . $e->getMessage());
            return 0;
        }
    }
}
