<?php
namespace Models;

use Services\QueryLogger;

abstract class Model extends BaseModel {
    protected static ?string $table = null;
    protected static string $primaryKey = 'id';
    protected array $attributes = [];
    protected bool $exists = false;

    public function __construct($pdo, array $attributes = []) {
        parent::__construct($pdo);
        $this->attributes = $attributes;
        if (!empty($attributes)) {
            $this->exists = true;
        }
    }

    public static function table(string $table): static {
        static::$table = $table;
        return new static($GLOBALS['pdo'] ?? null);
    }

    public static function find(mixed $id): ?static {
        $pdo = $GLOBALS['pdo'] ?? null;
        if (!$pdo) return null;
        $table = static::$table;
        $pk = static::$primaryKey;
        $sql = "SELECT * FROM `{$table}` WHERE `{$pk}` = :id LIMIT 1";
        $row = static::queryOne($sql, [':id' => $id]);
        if ($row) {
            return new static($pdo, $row);
        }
        return null;
    }

    public static function where(array $conditions): array {
        $pdo = $GLOBALS['pdo'] ?? null;
        if (!$pdo) return [];
        $table = static::$table;
        $sql = "SELECT * FROM `{$table}`";
        $params = [];
        $where = [];
        foreach ($conditions as $col => $val) {
            $where[] = "`{$col}` = :{$col}";
            $params[":{$col}"] = $val;
        }
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $rows = static::queryAll($sql, $params);
        return array_map(fn($r) => new static($pdo, $r), $rows);
    }

    public static function all(): array {
        $pdo = $GLOBALS['pdo'] ?? null;
        if (!$pdo) return [];
        $table = static::$table;
        $sql = "SELECT * FROM `{$table}`";
        $rows = static::queryAll($sql);
        return array_map(fn($r) => new static($pdo, $r), $rows);
    }

    public static function create(array $data): ?static {
        $pdo = $GLOBALS['pdo'] ?? null;
        if (!$pdo) return null;
        $table = static::$table;
        $cols = array_keys($data);
        $placeholders = array_map(fn($c) => ':' . $c, $cols);
        $sql = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $cols) . '`) VALUES (' . implode(',', $placeholders) . ')';
        $params = [];
        foreach ($data as $k => $v) $params[':' . $k] = $v;
        $id = static::insertGetId($sql, $params);
        return static::find((int)$id);
    }

    public function update(array $data): bool {
        $pdo = $this->pdo;
        if (!$pdo || !$this->exists) return false;
        $table = static::$table;
        $pk = static::$primaryKey;
        $sets = [];
        $params = [];
        foreach ($data as $col => $val) {
            $sets[] = '`' . $col . '` = :' . $col;
            $params[':' . $col] = $val;
        }
        $params[':id'] = $this->attributes[$pk] ?? null;
        $sql = 'UPDATE `' . $table . '` SET ' . implode(',', $sets) . ' WHERE `' . $pk . '` = :id';
        $affected = $this->execute($sql, $params);
        if ($affected > 0) {
            $this->attributes = array_merge($this->attributes, $data);
        }
        return $affected > 0;
    }

    public function delete(): bool {
        $pdo = $this->pdo;
        if (!$pdo || !$this->exists) return false;
        $table = static::$table;
        $pk = static::$primaryKey;
        $sql = 'DELETE FROM `' . $table . '` WHERE `' . $pk . '` = :id';
        $affected = $this->execute($sql, [':id' => $this->attributes[$pk] ?? null]);
        if ($affected > 0) {
            $this->exists = false;
            $this->attributes = [];
        }
        return $affected > 0;
    }

    public function toArray(): array {
        return $this->attributes;
    }

    public function get(string $key, mixed $default = null): mixed {
        return $this->attributes[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void {
        $this->attributes[$key] = $value;
    }

    public function isExists(): bool {
        return $this->exists;
    }
}
