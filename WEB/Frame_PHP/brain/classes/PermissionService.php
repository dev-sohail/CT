<?php
namespace Services;

use PDO;

class PermissionService {
    private $pdo;
    private $defaultPermissions = [];

    private static $schemaEnsured = false;
    private static $seeded = false;
    private static $permMapCache = null;

    public function __construct($pdo, array $defaultPermissions = []) {
        $this->pdo = $pdo;
        $this->defaultPermissions = $defaultPermissions;
        if (!self::$schemaEnsured) {
            $this->ensureSchema();
            self::$schemaEnsured = true;
        }
        if (!self::$seeded && !empty($defaultPermissions)) {
            $this->seedDefaults();
            self::$seeded = true;
        }
    }

    private function ensureSchema() {
        try {
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS permissions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                `key` VARCHAR(100) UNIQUE NOT NULL,
                name VARCHAR(255) NOT NULL
            )");
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS role_permissions (
                role VARCHAR(50) NOT NULL,
                permission_key VARCHAR(100) NOT NULL,
                PRIMARY KEY(role, permission_key)
            )");
        } catch (\PDOException $e) {
        }
    }

    private function seedDefaults() {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) AS c FROM permissions");
            $count = (int)($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
            if ($count === 0) {
                $ins = $this->pdo->prepare("INSERT INTO permissions (`key`, name) VALUES (?, ?)");
                foreach ($this->defaultPermissions as $p) {
                    $ins->execute([$p['key'], $p['name']]);
                }
            }
        } catch (\PDOException $e) {
        }
    }

    public function listPermissions() {
        try {
            $stmt = $this->pdo->query("SELECT `key`, name FROM permissions ORDER BY name ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return $this->defaultPermissions;
        }
    }

    public function listRolePermissions($role) {
        try {
            $stmt = $this->pdo->query("SELECT role, permission_key FROM role_permissions");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $keys = [];
            foreach ($rows as $row) {
                $rolesStr = $row['role'] ?? '';
                if ($rolesStr === $role) {
                    $keys[] = $row['permission_key'];
                    continue;
                }
                $parts = array_filter(array_map(function ($r) {
                    return trim($r, " \t\n\r\0\x0B'\"");
                }, preg_split('/\s*,\s*/', $rolesStr)));
                if (in_array($role, $parts, true)) {
                    $keys[] = $row['permission_key'];
                }
            }
            return array_values(array_unique($keys));
        } catch (\PDOException $e) {
            return [];
        }
    }

    public function setRolePermissions($role, array $keys) {
        try {
            $this->pdo->prepare("DELETE FROM role_permissions WHERE role = ?")->execute([$role]);
            if (!empty($keys)) {
                $ins = $this->pdo->prepare("INSERT INTO role_permissions (role, permission_key) VALUES (?, ?)");
                foreach ($keys as $k) {
                    $ins->execute([$role, $k]);
                }
            }
            self::$permMapCache = null;
        } catch (\PDOException $e) {
        }
    }

    private function parseRoles($rolesStr) {
        $parts = array_filter(array_map(function ($r) {
            return trim($r, " \t\n\r\0\x0B'\"");
        }, preg_split('/\s*,\s*/', (string)$rolesStr)));
        return array_values(array_unique($parts));
    }

    public function ensurePermissionRoles($permissionKey, array $rolesToInclude) {
        try {
            $stmt = $this->pdo->prepare("SELECT role FROM role_permissions WHERE permission_key = ?");
            $stmt->execute([$permissionKey]);
            $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $existing = [];
            foreach ($rows as $rstr) {
                $existing = array_merge($existing, $this->parseRoles($rstr));
            }
            $union = array_values(array_unique(array_merge($existing, $rolesToInclude)));
            $this->pdo->prepare("DELETE FROM role_permissions WHERE permission_key = ?")->execute([$permissionKey]);
            $csv = implode(',', $union);
            $ins = $this->pdo->prepare("INSERT INTO role_permissions (role, permission_key) VALUES (?, ?)");
            $ins->execute([$csv, $permissionKey]);
            self::$permMapCache = null;
        } catch (\PDOException $e) {
        }
    }

    public function can($role, $permissionKey) {
        if ($role === 'admin') return true;
        $map = $this->loadPermissionMap();
        $roles = $map[$permissionKey] ?? [];
        return in_array($role, $roles, true);
    }

    private function loadPermissionMap(): array {
        if (self::$permMapCache !== null) {
            return self::$permMapCache;
        }
        $map = [];
        try {
            $stmt = $this->pdo->query("SELECT role, permission_key FROM role_permissions");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $key = $row['permission_key'] ?? '';
                if ($key === '') continue;
                if (!isset($map[$key])) $map[$key] = [];
                foreach ($this->parseRoles($row['role'] ?? '') as $r) {
                    if (!in_array($r, $map[$key], true)) $map[$key][] = $r;
                }
            }
        } catch (\PDOException $e) {
            error_log("PermissionService: failed to load permission map: " . $e->getMessage());
        }
        self::$permMapCache = $map;
        return $map;
    }
}
