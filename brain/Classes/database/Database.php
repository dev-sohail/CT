<?php

declare(strict_types=1);

class Database
{
    private ?PDO $connection = null;
    private ?PDOStatement $statement = null;
    private array $log = [];
    private bool $logging = false;

    public function __construct(array $config = [])
    {
        $this->initializeConnection($config);
    }

    private function initializeConnection(array $config): void
    {
        if (empty($config)) {
            $config = [
                'hostname' => defined('DB_HOST') ? DB_HOST : 'localhost',
                'username' => defined('DB_USERNAME') ? DB_USERNAME : 'root',
                'password' => defined('DB_PASSWORD') ? DB_PASSWORD : '',
                'database' => defined('DB_DATABASE') ? DB_DATABASE : 'ct_frame',
                'port' => defined('DB_PORT') ? DB_PORT : 3306,
                'charset' => defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4',
            ];
        }

        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%d;dbname=%s;charset=%s",
                $config['hostname'],
                $config['port'],
                $config['database'],
                $config['charset']
            );

            $this->connection = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_PERSISTENT => false,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            throw new RuntimeException('Database connection failed');
        }
    }

    public function prepare(string $sql): void
    {
        if (!$this->connection) throw new RuntimeException('No database connection');
        $this->statement = $this->connection->prepare($sql);
    }

    public function bindParam(string|int $parameter, mixed &$variable, int $data_type = PDO::PARAM_STR, int $length = 0): void
    {
        if (!$this->statement) throw new RuntimeException('No prepared statement');
        $length > 0 
            ? $this->statement->bindParam($parameter, $variable, $data_type, $length)
            : $this->statement->bindParam($parameter, $variable, $data_type);
    }

    public function bindValue(string|int $parameter, mixed $value, int $data_type = PDO::PARAM_STR): void
    {
        if (!$this->statement) throw new RuntimeException('No prepared statement');
        $this->statement->bindValue($parameter, $value, $data_type);
    }

    public function execute(): object
    {
        if (!$this->statement) throw new RuntimeException('No prepared statement');
        $this->statement->execute();
        return $this->formatResult($this->statement);
    }

    public function query(string $sql, array $params = []): object
    {
        if (!$this->connection) throw new RuntimeException('No database connection');
        $stmt = $this->connection->prepare($sql);
        if ($this->logging) $this->log[] = ['sql' => $sql, 'params' => $params, 'time' => microtime(true)];
        $stmt->execute($params);
        return $this->formatResult($stmt);
    }

    public function run(string $sql, array $params = []): int
    {
        if (!$this->connection) throw new RuntimeException('No database connection');
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function escape(string $value): string
    {
        if (!$this->connection) throw new RuntimeException('No database connection');
        return substr($this->connection->quote($value), 1, -1);
    }

    public function countAffected(): int
    {
        return $this->statement ? $this->statement->rowCount() : 0;
    }

    public function getLastId(): string
    {
        if (!$this->connection) throw new RuntimeException('No database connection');
        return $this->connection->lastInsertId();
    }

    public function beginTransaction(): bool
    {
        if (!$this->connection) throw new RuntimeException('No database connection');
        return $this->connection->beginTransaction();
    }

    public function commit(): bool
    {
        if (!$this->connection) throw new RuntimeException('No database connection');
        return $this->connection->commit();
    }

    public function rollback(): bool
    {
        if (!$this->connection) throw new RuntimeException('No database connection');
        return $this->connection->rollback();
    }

    public function isConnected(): bool
    {
        try {
            return $this->connection && $this->connection->query('SELECT 1') !== false;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getPDO(): ?PDO
    {
        return $this->connection;
    }

    public function enableLogging(): void
    {
        $this->logging = true;
    }

    public function disableLogging(): void
    {
        $this->logging = false;
    }

    public function getLog(): array
    {
        return $this->log;
    }

    public function clearLog(): void
    {
        $this->log = [];
    }

    private function formatResult(PDOStatement $stmt): object
    {
        return (object) [
            'row' => $stmt->fetch(),
            'rows' => $stmt->fetchAll(),
            'num_rows' => $stmt->rowCount(),
            'statement' => $stmt
        ];
    }

    public function __destruct()
    {
        $this->connection = null;
    }
}
