<?php
namespace Services;

use PDO;

class MigrationService {
    private $pdo;
    private $dir;
    private $inTransaction = false;
    public function __construct($pdo, $dir = null) {
        $this->pdo = $pdo;
        $this->dir = $dir ?: (defined('APP_DATABASE') ? (APP_DATABASE . '/migrations') : (__DIR__ . '/../../storage/database/migrations'));
        $this->ensureTable();
    }
    private function ensureTable() {
        try {
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS `migrations` (`id` VARCHAR(255) PRIMARY KEY, `applied_at` DATETIME DEFAULT CURRENT_TIMESTAMP)");
        } catch (\PDOException $e) {
            error_log('Migration ensureTable error: ' . $e->getMessage());
            try {
                $this->pdo->exec("CREATE TABLE IF NOT EXISTS `migrations` (`id` VARCHAR(255) PRIMARY KEY, `applied_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP)");
            } catch (\PDOException $e2) {
                error_log('Migration ensureTable fallback error: ' . $e2->getMessage());
            }
        }
    }
    private function getApplied() {
        $applied = [];
        try {
            $stmt = $this->pdo->query("SELECT id FROM migrations");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) { $applied[] = $row['id']; }
        } catch (\PDOException $e) {
            error_log('Migration getApplied error: ' . $e->getMessage());
        }
        return $applied;
    }
    private function loadMigrations() {
        $list = [];
        if (!is_dir($this->dir)) { @mkdir($this->dir, 0775, true); }
        $files = glob(rtrim($this->dir, '/\\') . DIRECTORY_SEPARATOR . '*.php');
        sort($files);
        foreach ($files as $file) {
            $def = include $file;
            if (is_array($def) && isset($def['id']) && isset($def['up']) && is_callable($def['up'])) {
                $list[] = $def;
            }
        }
        return $list;
    }
    public function applyPending(&$results = []) {
        $this->ensureTable();
        $applied = $this->getApplied();
        $defs = $this->loadMigrations();
        foreach ($defs as $def) {
            $id = $def['id'];
            if (in_array($id, $applied)) {
                $results[] = "SKIP $id";
                continue;
            }
            try {
                $this->begin();
                $def['up']($this->pdo);
                try {
                    $stmt = $this->pdo->prepare("INSERT INTO migrations (id) VALUES (?)");
                    $stmt->execute([$id]);
                } catch (\PDOException $e) {
                    // If migrations table is unavailable, skip marking and continue
                }
                $this->commit();
                $results[] = "APPLY $id";
            } catch (\Throwable $e) {
                $this->rollbackSilently();
                $results[] = "ERROR $id: " . $e->getMessage();
                break;
            }
        }
        return $results;
    }

    public function run(array $queries, &$results = []) {
        $this->begin();
        foreach ($queries as $name => $sql) {
            try {
                $this->pdo->exec($sql);
                $results[] = "OK $name";
            } catch (\PDOException $e) {
                $results[] = "ERR $name: " . $e->getMessage();
            }
        }
        $this->commit();
        return $results;
    }

    public function ensureTableExists($table, $createSql) {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
            $stmt->execute([$table]);
            $exists = (int)$stmt->fetchColumn() > 0;
            if (!$exists) { $this->pdo->exec($createSql); }
        } catch (\PDOException $e) {
            error_log('Migration ensureTableExists error: ' . $e->getMessage());
            try { $this->pdo->exec($createSql); } catch (\PDOException $e2) {
                error_log('Migration ensureTableExists fallback error: ' . $e2->getMessage());
            }
        }
    }

    public function ensureColumn($table, $column, $definition) {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
            $stmt->execute([$table, $column]);
            $exists = (int)$stmt->fetchColumn() > 0;
            if (!$exists) {
                $this->pdo->exec("ALTER TABLE {$table} ADD COLUMN {$definition}");
            }
        } catch (\PDOException $e) {
            error_log('Migration ensureColumn error: ' . $e->getMessage());
        }
    }

    public function ensureIndex($table, $indexName, $indexSql) {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?");
            $stmt->execute([$table, $indexName]);
            $exists = (int)$stmt->fetchColumn() > 0;
            if (!$exists) { $this->pdo->exec($indexSql); }
        } catch (\PDOException $e) {
            error_log('Migration ensureIndex error: ' . $e->getMessage());
        }
    }

    private function begin() {
        if (method_exists($this->pdo, 'beginTransaction')) {
            try { $this->pdo->beginTransaction(); $this->inTransaction = true; } catch (\Throwable $e) { $this->inTransaction = false; }
        }
    }
    private function commit() {
        if ($this->inTransaction && method_exists($this->pdo, 'commit')) {
            try { $this->pdo->commit(); } catch (\Throwable $e) {
                error_log('Migration commit error: ' . $e->getMessage());
            }
        }
        $this->inTransaction = false;
    }
    private function rollbackSilently() {
        if ($this->inTransaction && method_exists($this->pdo, 'rollBack')) {
            try { $this->pdo->rollBack(); } catch (\Throwable $e) {
                error_log('Migration rollback error: ' . $e->getMessage());
            }
        }
        $this->inTransaction = false;
    }
}
