<?php
namespace Models;

use Services\QueryLogger;

class BaseModel {
    protected $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    protected function queryAll(string $sql, array $params = []): array {
        if (QueryLogger::enabled()) {
            $start = microtime(true);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if (QueryLogger::enabled()) {
            QueryLogger::log($sql, $params, (microtime(true) - $start) * 1000);
        }
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    protected function queryOne(string $sql, array $params = []): ?array {
        if (QueryLogger::enabled()) {
            $start = microtime(true);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if (QueryLogger::enabled()) {
            QueryLogger::log($sql, $params, (microtime(true) - $start) * 1000);
        }
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    protected function queryValue(string $sql, array $params = []) {
        if (QueryLogger::enabled()) {
            $start = microtime(true);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if (QueryLogger::enabled()) {
            QueryLogger::log($sql, $params, (microtime(true) - $start) * 1000);
        }
        $value = $stmt->fetchColumn();
        return $value === false ? null : $value;
    }

    protected function execute(string $sql, array $params = []): int {
        if (QueryLogger::enabled()) {
            $start = microtime(true);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if (QueryLogger::enabled()) {
            QueryLogger::log($sql, $params, (microtime(true) - $start) * 1000);
        }
        return $stmt->rowCount();
    }

    protected function insertGetId(string $sql, array $params = []): string {
        if (QueryLogger::enabled()) {
            $start = microtime(true);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if (QueryLogger::enabled()) {
            QueryLogger::log($sql, $params, (microtime(true) - $start) * 1000);
        }
        return $this->pdo->lastInsertId();
    }

    protected function ensureTableExists(string $table, string $createSql): void
    {
        try {
            $stmt = $this->pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            if (!$stmt->fetchColumn()) {
                $this->pdo->exec($createSql);
            }
        } catch (\PDOException $e) {
            error_log('BaseModel::ensureTableExists ' . $table . ': ' . $e->getMessage());
        }
    }

    protected function ensureColumn(string $table, string $column, string $definition): void
    {
        try {
            $stmt = $this->pdo->prepare("SHOW COLUMNS FROM `$table` WHERE Field = ?");
            $stmt->execute([$column]);
            if (!$stmt->fetchColumn()) {
                $this->pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            }
        } catch (\PDOException $e) {
            error_log('BaseModel::ensureColumn ' . $table . '.' . $column . ': ' . $e->getMessage());
        }
    }

    protected function ensureIndex(string $table, string $indexName, string $indexSql): void
    {
        try {
            $stmt = $this->pdo->prepare("SHOW INDEX FROM `$table` WHERE Key_name = ?");
            $stmt->execute([$indexName]);
            if (!$stmt->fetchColumn()) {
                $this->pdo->exec($indexSql);
            }
        } catch (\PDOException $e) {
            error_log('BaseModel::ensureIndex ' . $table . '.' . $indexName . ': ' . $e->getMessage());
        }
    }
}
