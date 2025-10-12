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
     * Initialize database connection
     */
    private function initializeDatabase(): void
    {
        // Try to get database from registry
        if ($this->registry->has('database')) {
            $this->db = $this->registry->get('database');
        } elseif ($this->registry->has('db')) {
            $this->db = $this->registry->get('db');
        } else {
            // Create a new database connection if none exists
            try {
                require_once ROOT . '/Brain/Classes/database/Database.php';
                $this->db = new Database();
                $this->registry->set('database', $this->db);
            } catch (Exception $e) {
                error_log('Failed to initialize database: ' . $e->getMessage());
                $this->db = null;
            }
        }
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
        return $this->db !== null && method_exists($this->db, 'isConnected') && $this->db->isConnected();
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
     * Execute a query
     * 
     * @param string $sql The SQL query
     * @param array $params Query parameters
     * @return mixed Query result
     */
    protected function query(string $sql, array $params = []): mixed
    {
        if (!$this->hasDatabase()) {
            error_log('Database not available for query: ' . $sql);
            return null;
        }

        try {
            $db = $this->getDatabase();
            
            if (method_exists($db, 'query')) {
                return $db->query($sql, $params);
            }
            
            throw new RuntimeException('Database query method not available');
        } catch (Exception $e) {
            error_log('Database query error: ' . $e->getMessage() . ' SQL: ' . $sql);
            return null;
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
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $result = $this->query($sql, [$id]);
        
        if (is_object($result) && method_exists($result, 'row')) {
            return $result->row;
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
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "{$field} = ?";
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
        
        $result = $this->query($sql, $params);
        
        if (is_object($result) && method_exists($result, 'rows')) {
            return $result->rows;
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
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $result = $this->query($sql, array_values($data));
        
        $db = $this->getDatabase();
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
        $fields = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            $fields[] = "{$field} = ?";
            $params[] = $value;
        }
        
        $params[] = $id;
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE {$this->primaryKey} = ?";
        
        $result = $this->query($sql, $params);
        
        return true; // Assume success if no exception thrown
    }

    /**
     * Delete a record
     * 
     * @param int|string $id The record ID
     * @return bool True if successful
     */
    public function delete(int|string $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        
        $result = $this->query($sql, [$id]);
        
        return true; // Assume success if no exception thrown
    }

    /**
     * Count records
     * 
     * @param array $conditions Query conditions
     * @return int The count
     */
    public function count(array $conditions = []): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "{$field} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        $result = $this->query($sql, $params);
        
        if (is_object($result) && method_exists($result, 'row')) {
            return (int) $result->row['count'];
        }
        
        return 0;
    }
}
