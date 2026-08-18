<?php
declare(strict_types=1);

/* ---------- CLI Mode ---------- */
if (PHP_SAPI === 'cli' || isset($argv) && !empty($argv) && basename($argv[0]) === basename(__FILE__)) {
putenv('DB_CONNECTION=pgsql');
putenv('DB_HOST=postgres');
putenv('DB_PORT=5432');
putenv('DB_DATABASE=edu_ct');
putenv('DB_USERNAME=root');
putenv('DB_PASSWORD=root');
    putenv('DB_CHARSET=utf8mb4');

    $command = $argv[1] ?? 'help';
    $arg1 = $argv[2] ?? null;
    $arg2 = $argv[3] ?? null;

    require_once __DIR__ . '/storage/vendor/autoload.php';

    if (!defined('APP_ROOT')) {
        require_once __DIR__ . '/brain/config.php';
    }

    $pdo = $GLOBALS['pdo'] ?? null;
    if (!$pdo instanceof PDO) {
        echo "Error: Database connection not available.\n";
        exit(1);
    }

    switch ($command) {
        case 'migrate':
            echo "Running migrations...\n";
            $m = new Services\MigrationService($pdo, APP_DATABASE . '/migrations');
            $results = [];
            $m->applyPending($results);
            foreach ($results as $r) {
                echo "  $r\n";
            }
            break;

        case 'migrate:fresh':
            echo "Dropping all tables and re-running migrations...\n";
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $pdo->beginTransaction();
            try {
                foreach ($tables as $table) {
                    if ($table !== 'migrations') {
                        $pdo->exec("DROP TABLE IF EXISTS `$table`");
                        echo "  Dropped: $table\n";
                    }
                }
                $pdo->exec("DROP TABLE IF EXISTS migrations");
                $pdo->commit();
            } catch (Throwable $e) {
                $pdo->rollBack();
                echo "  Error: " . $e->getMessage() . "\n";
                exit(1);
            }
            $m = new Services\MigrationService($pdo, APP_DATABASE . '/migrations');
            $results = [];
            $m->applyPending($results);
            foreach ($results as $r) {
                echo "  $r\n";
            }
            break;

        case 'make:module':
            if (!$arg1) {
                echo "Usage: php cli.php make:module <module_name>\n";
                exit(1);
            }
            $module = ucfirst(strtolower(trim($arg1)));
            $modulePath = APP_CONTROLLERS . '/' . strtolower($module);
            $modelPath = APP_MODELS . '/' . strtolower($module);
            $viewPath = APP_VIEWS . '/' . strtolower($module);

            @mkdir($modulePath . '/controllers', 0755, true);
            @mkdir($modulePath . '/models', 0755, true);
            @mkdir($viewPath, 0755, true);

            $controllerFile = $modulePath . '/controllers/' . $module . 'Controller.php';
            $modelFile = $modulePath . '/models/' . $module . 'Model.php';

            if (!file_exists($controllerFile)) {
                $content = "<?php\n\nnamespace Controllers\\$module;\n\nuse Controllers\BaseController;\nuse Models\\$module\\{$module}Model;\n\nclass {$module}Controller extends BaseController\n{\n    public function index()\n    {\n        if (!isset(\$_SESSION['logged_in']) || \$_SESSION['logged_in'] !== true || (\$_SESSION['role'] ?? '') !== 'admin') {\n            header('Location: ' . APP_ROOT_URL . '/admin/login');\n            exit();\n        }\n        \$model = new {$module}Model(\$this->pdo);\n        \$data = [];\n        // TODO: Add your logic here\n        \$this->view('{$module}/index', \$data);\n    }\n}\n";
                file_put_contents($controllerFile, $content);
                echo "  Created: $controllerFile\n";
            } else {
                echo "  Exists: $controllerFile\n";
            }

            if (!file_exists($modelFile)) {
                $content = "<?php\n\nnamespace Models\\$module;\n\nuse Models\BaseModel;\n\nclass {$module}Model extends BaseModel\n{\n    // TODO: Add your model methods here\n}\n";
                file_put_contents($modelFile, $content);
                echo "  Created: $modelFile\n";
            } else {
                echo "  Exists: $modelFile\n";
            }

            echo "Module '$module' created successfully.\n";
            break;

        case 'make:controller':
            if (!$arg1) {
                echo "Usage: php cli.php make:controller <ControllerName>\n";
                exit(1);
            }
            $name = ucfirst(strtolower(trim($arg1)));
            $file = APP_CONTROLLERS . '/' . $name . 'Controller.php';
            if (!file_exists($file)) {
                $content = "<?php\n\nnamespace Controllers;\n\nuse Controllers\BaseController;\n\nclass {$name}Controller extends BaseController\n{\n    public function index()\n    {\n        \$this->view('{$name}/index');\n    }\n}\n";
                file_put_contents($file, $content);
                echo "Created: $file\n";
            } else {
                echo "Exists: $file\n";
            }
            break;

        case 'make:model':
            if (!$arg1) {
                echo "Usage: php cli.php make:model <ModelName>\n";
                exit(1);
            }
            $name = ucfirst(strtolower(trim($arg1)));
            $file = APP_MODELS . '/' . $name . 'Model.php';
            if (!file_exists($file)) {
                $content = "<?php\n\nnamespace Models;\n\nuse Models\BaseModel;\n\nclass {$name}Model extends BaseModel\n{\n    // TODO: Add your model methods here\n}\n";
                file_put_contents($file, $content);
                echo "Created: $file\n";
            } else {
                echo "Exists: $file\n";
            }
            break;

        case 'make:migration':
            if (!$arg1) {
                echo "Usage: php cli.php make:migration <migration_name>\n";
                exit(1);
            }
            $name = strtolower(trim($arg1));
            $timestamp = date('Ymd_His');
            $fileName = $timestamp . '_' . $name . '.php';
            $file = APP_DATABASE . '/migrations/' . $fileName;
            if (!file_exists($file)) {
                $content = "<?php\n\nreturn [\n    'id' => '{$timestamp}_{$name}',\n    'up' => function (\$pdo) {\n        // TODO: Add your migration SQL here\n    },\n    'down' => function (\$pdo) {\n        // TODO: Add your rollback SQL here\n    }\n];\n";
                file_put_contents($file, $content);
                echo "Created: $file\n";
            } else {
                echo "Exists: $file\n";
            }
            break;

        case 'cache:clear':
            echo "Clearing caches...\n";
            $dirs = [APP_STORAGE . '/cache', APP_STORAGE . '/logs'];
            foreach ($dirs as $dir) {
                if (is_dir($dir)) {
                    $files = glob($dir . '/*');
                    foreach ($files as $f) {
                        @unlink($f);
                    }
                    echo "  Cleared: $dir\n";
                }
            }
            if (class_exists(Services\RedisService::class)) {
                try {
                    $redis = new Services\RedisService();
                    if ($redis->enabled()) {
                        $redis->flushAll();
                        echo "  Redis cache cleared\n";
                    }
                } catch (Throwable $e) {
                    echo "  Redis error: " . $e->getMessage() . "\n";
                }
            }
            echo "Cache cleared.\n";
            break;

        case 'db:seed':
            echo "Seeding database...\n";
            $seedFile = APP_DATABASE . '/seed_db.php';
            if (file_exists($seedFile)) {
                $seed = include $seedFile;
                if (is_array($seed)) {
                    foreach ($seed as $row) {
                        if (isset($row[0], $row[1])) {
                            try {
                                $stmt = $pdo->prepare("INSERT IGNORE INTO site_settings (`key`,`value`) VALUES (?,?)");
                                $stmt->execute([$row[0], $row[1]]);
                                echo "  Seeded: {$row[0]}\n";
                            } catch (\PDOException $e) {
                                echo "  Error seeding {$row[0]}: " . $e->getMessage() . "\n";
                            }
                        }
                    }
                }
            }
            echo "Database seeded.\n";
            break;

        case 'user:create':
            if (!$arg1 || !$arg2) {
                echo "Usage: php cli.php user:create <username> <password> [role]\n";
                exit(1);
            }
            $username = trim($arg1);
            $password = trim($arg2);
            $role = $arg3 ?? 'user';
            $validRoles = ['admin', 'user'];
            if (!in_array($role, $validRoles)) {
                echo "Invalid role. Valid roles: " . implode(', ', $validRoles) . "\n";
                exit(1);
            }
            $auth = new Services\AuthService($pdo);
            $result = $auth->register($username, $password, $username . '@example.com', $role);
            if ($result->status) {
                echo "User created: $username (role: $role)\n";
            } else {
                echo "Error: " . $result->message . "\n";
                exit(1);
            }
            break;

        case 'list:routes':
            echo "Available routes:\n";
            $routesFile = APP_ROUTES . '/web.php';
            if (file_exists($routesFile)) {
                $content = file_get_contents($routesFile);
                preg_match_all("/add_app_route\(\\\$r,\s*['\"]([^'\"]+)['\"],\s*['\"]([^'\"]+)['\"],\s*'([^']+)'/", $content, $matches, PREG_SET_ORDER);
                if (!empty($matches)) {
                    foreach ($matches as $m) {
                        echo "  {$m[1]} {$m[2]} => {$m[3]}\n";
                    }
                } else {
                    echo "  No routes found or parse error.\n";
                }
            } else {
                echo "  Routes file not found.\n";
            }
            break;

        case 'db:backup':
            $backupFile = $arg1 ?? (APP_STORAGE . '/backups/backup_' . date('Ymd_His') . '.sql');
            @mkdir(dirname($backupFile), 0775, true);
            $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
            $sql = '';
            foreach ($tables as $table) {
                $sql .= 'DROP TABLE IF EXISTS `' . $table . '`;' . PHP_EOL;
                $create = $pdo->query('SHOW CREATE TABLE `' . $table . '`')->fetch(PDO::FETCH_ASSOC);
                $sql .= ($create['Create Table'] ?? '') . ';' . PHP_EOL;
                $rows = $pdo->query('SELECT * FROM `' . $table . '`')->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as $row) {
                    $vals = array_map(function ($v) use ($pdo) {
                        if ($v === null) return 'NULL';
                        return $pdo->quote((string)$v);
                    }, $row);
                    $sql .= 'INSERT INTO `' . $table . '` VALUES (' . implode(',', $vals) . ');' . PHP_EOL;
                }
            }
            file_put_contents($backupFile, $sql);
            echo "Backup saved to: $backupFile\n";
            break;

        case 'db:restore':
            $restoreFile = $arg1 ?? null;
            if (!$restoreFile || !file_exists($restoreFile)) {
                echo "Usage: php cli.php db:restore <backup_file.sql>\n";
                exit(1);
            }
            $sql = file_get_contents($restoreFile);
            $pdo->beginTransaction();
            try {
                $pdo->exec($sql);
                $pdo->commit();
                echo "Database restored from: $restoreFile\n";
            } catch (\Throwable $e) {
                $pdo->rollBack();
                echo "Restore failed: " . $e->getMessage() . "\n";
                exit(1);
            }
            break;

        case 'help':
        default:
            echo "Frame PHP CLI Tool\n";
            echo "Usage: php cli.php <command> [arguments]\n\n";
            echo "Available commands:\n";
            echo "  migrate                    Run pending migrations\n";
            echo "  migrate:fresh              Drop all tables and re-run migrations\n";
            echo "  make:module <name>         Create a new module (controllers, models, views)\n";
            echo "  make:controller <name>     Create a new controller\n";
            echo "  make:model <name>          Create a new model\n";
            echo "  make:migration <name>      Create a new migration file\n";
            echo "  cache:clear                Clear application and Redis cache\n";
            echo "  db:seed                    Seed database with default data\n";
            echo "  user:create <u> <p> [r]    Create a new user\n";
            echo "  list:routes                 List all registered routes\n";
            echo "  db:backup [file]            Backup database to SQL file\n";
            echo "  db:restore <file>           Restore database from SQL file\n";
            echo "  help                        Show this help message\n";
            break;
    }
    exit;
}
