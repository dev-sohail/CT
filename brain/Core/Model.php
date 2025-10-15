<?php

declare(strict_types=1);

/**
 * Base Model Class
 * 
 * Provides common functionality for all models in the framework
 */
abstract class Model
{
    protected object $registry;
    protected ?object $db = null;
    protected string $table = '';
    protected string $primaryKey = 'id';

    public function __construct(object $registry)
    {
        $this->registry = $registry;
        $this->initializeDatabase();
    }

    /**
     * Initialize database connection from Registry
     * Database should already be registered by Bootstrap
     */
    private function initializeDatabase(): void
    {
        // Try multiple registry keys (for flexibility)
        $dbKeys = ['pdo', 'db', 'database'];
        
        foreach ($dbKeys as $key) {
            if ($this->registry->has($key)) {
                $this->db = $this->registry->get($key);
                if ($this->db instanceof \PDO) {
                    return;
                }
            }
        }
        
        // Database not found in registry - log warning
        error_log('Warning: Database not found in registry. Ensure Bootstrap initializes PDO.');
        $this->db = null;
    }

    /**
     * Get a service from the registry
     * 
     * @param string $key The service key
     * @return mixed The service instance
     */
    public function __get(string $key): mixed
    {
        return $this->registry->get($key);
    }

    /**
     * Set a service or shared property in registry
     * 
     * @param string $key The property key
     * @param mixed $value The property value
     */
    public function __set(string $key, mixed $value): void
    {
        $this->registry->set($key, $value);
    }

    /**
     * Get the table name for this model
     * 
     * @return string The table name
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Get the primary key for this model
     * 
     * @return string The primary key field name
     */
    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    /**
     * Check if database connection is available
     * 
     * @return bool True if database is available
     */
    protected function hasDatabase(): bool
    {
        if ($this->db === null) {
            return false;
        }
        
        // For PDO instances
        if ($this->db instanceof \PDO) {
            try {
                // Simple ping to check connection
                $this->db->query('SELECT 1');
                return true;
            } catch (\PDOException $e) {
                return false;
            }
        }
        
        // For custom database wrappers
        if (method_exists($this->db, 'isConnected')) {
            return $this->db->isConnected();
        }
        
        return true; // Assume available if can't determine
    }

    /**
     * Get database instance
     * 
     * @return object The database instance
     * @throws RuntimeException If database is not available
     */
    protected function getDatabase(): object
    {
        if ($this->db === null) {
            throw new RuntimeException('Database connection not available');
        }
        return $this->db;
    }

    /**
     * Execute a query with PDO
     * 
     * @param string $sql The SQL query
     * @param array $params Query parameters
     * @return \PDOStatement|false Statement result
     */
    protected function query(string $sql, array $params = []): \PDOStatement|false
    {
        if (!$this->hasDatabase()) {
            throw new RuntimeException('Database not available. Ensure PDO is registered in Registry.');
        }

        try {
            $db = $this->getDatabase();
            
            // If it's a PDO instance, use prepared statements
            if ($db instanceof \PDO) {
                if (empty($params)) {
                    return $db->query($sql);
                } else {
                    $stmt = $db->prepare($sql);
                    $stmt->execute($params);
                    return $stmt;
                }
            }
            
            // If it's a custom wrapper with query method
            if (method_exists($db, 'query')) {
                return $db->query($sql, $params);
            }
            
            throw new RuntimeException('Database query method not available');
        } catch (\PDOException $e) {
            error_log("PDO Error in query '$sql': " . $e->getMessage());
            throw new RuntimeException('Database query failed: ' . $e->getMessage(), 0, $e);
        } catch (\Throwable $e) {
            error_log("Error in query '$sql': " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Find a record by ID
     * 
     * @param int|string $id The record ID
     * @return array|null The record data or null if not found
     */
    public function findById(int|string $id): ?array
    {
        if (empty($this->table)) {
            throw new RuntimeException('Model table name not set');
        }
        
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->query($sql, [$id]);
        
        if ($stmt instanceof \PDOStatement) {
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ?: null;
        }
        
        return null;
    }

    /**
     * Find all records
     * 
     * @param array $conditions Query conditions
     * @param int $limit Query limit
     * @param int $offset Query offset
     * @return array The records
     */
    public function findAll(array $conditions = [], int $limit = 0, int $offset = 0): array
    {
        if (empty($this->table)) {
            throw new RuntimeException('Model table name not set');
        }
        
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "`{$field}` = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        if ($limit > 0) {
            $sql .= " LIMIT {$limit}";
            if ($offset > 0) {
                $sql .= " OFFSET {$offset}";
            }
        }
        
        $stmt = $this->query($sql, $params);
        
        if ($stmt instanceof \PDOStatement) {
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
        
        return [];
    }

    /**
     * Insert a new record
     * 
     * @param array $data The record data
     * @return int|string The new record ID
     */
    public function insert(array $data): int|string
    {
        if (empty($this->table)) {
            throw new RuntimeException('Model table name not set');
        }
        
        if (empty($data)) {
            throw new \InvalidArgumentException('Insert data cannot be empty');
        }
        
        $fields = array_map(fn($field) => "`{$field}`", array_keys($data));
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $this->query($sql, array_values($data));
        
        // Get last insert ID
        $db = $this->getDatabase();
        if ($db instanceof \PDO) {
            return $db->lastInsertId();
        }
        
        if (method_exists($db, 'getLastId')) {
            return $db->getLastId();
        }
        
        return 0;
    }

    /**
     * Update a record
     * 
     * @param int|string $id The record ID
     * @param array $data The updated data
     * @return bool True if successful
     */
    public function update(int|string $id, array $data): bool
    {
        if (empty($this->table)) {
            throw new RuntimeException('Model table name not set');
        }
        
        if (empty($data)) {
            throw new \InvalidArgumentException('Update data cannot be empty');
        }
        
        $fields = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            $fields[] = "`{$field}` = ?";
            $params[] = $value;
        }
        
        $params[] = $id;
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE {$this->primaryKey} = ?";
        
        $stmt = $this->query($sql, $params);
        
        // Return true if query executed (even if no rows affected)
        return $stmt instanceof \PDOStatement;
    }

    /**
     * Delete a record
     * 
     * @param int|string $id The record ID
     * @return bool True if successful
     */
    public function delete(int|string $id): bool
    {
        if (empty($this->table)) {
            throw new RuntimeException('Model table name not set');
        }
        
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        
        $stmt = $this->query($sql, [$id]);
        
        return $stmt instanceof \PDOStatement;
    }

    /**
     * Count records
     * 
     * @param array $conditions Query conditions
     * @return int The count
     */
    public function count(array $conditions = []): int
    {
        if (empty($this->table)) {
            throw new RuntimeException('Model table name not set');
        }
        
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "`{$field}` = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        $stmt = $this->query($sql, $params);
        
        if ($stmt instanceof \PDOStatement) {
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ? (int)$result['count'] : 0;
        }
        
        return 0;
    }
    
    /**
     * Execute raw SQL (use with caution!)
     * 
     * @param string $sql The SQL query
     * @param array $params Query parameters
     * @return \PDOStatement|false
     */
    protected function execute(string $sql, array $params = []): \PDOStatement|false
    {
        return $this->query($sql, $params);
    }
    
    /**
     * Begin transaction
     * 
     * @return bool
     */
    protected function beginTransaction(): bool
    {
        $db = $this->getDatabase();
        if ($db instanceof \PDO) {
            return $db->beginTransaction();
        }
        return false;
    }
    
    /**
     * Commit transaction
     * 
     * @return bool
     */
    protected function commit(): bool
    {
        $db = $this->getDatabase();
        if ($db instanceof \PDO) {
            return $db->commit();
        }
        return false;
    }
    
    /**
     * Rollback transaction
     * 
     * @return bool
     */
    protected function rollback(): bool
    {
        $db = $this->getDatabase();
        if ($db instanceof \PDO) {
            return $db->rollback();
        }
        return false;
    }
}

