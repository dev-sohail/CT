<?php

declare(strict_types=1);

class Transaction
{
    protected \PDO $pdo;
    protected bool $active = false;
    protected array $savepoints = [];
    protected int $savepointCounter = 0;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function begin(): bool
    {
        if ($this->active) {
            throw new RuntimeException('Transaction already active');
        }

        $result = $this->pdo->beginTransaction();
        $this->active = $result;
        return $result;
    }

    public function commit(): bool
    {
        if (!$this->active) {
            throw new RuntimeException('No active transaction to commit');
        }

        $result = $this->pdo->commit();
        $this->active = false;
        $this->savepoints = [];
        $this->savepointCounter = 0;
        return $result;
    }

    public function rollback(): bool
    {
        if (!$this->active) {
            throw new RuntimeException('No active transaction to rollback');
        }

        $result = $this->pdo->rollBack();
        $this->active = false;
        $this->savepoints = [];
        $this->savepointCounter = 0;
        return $result;
    }

    public function savepoint(string $name): bool
    {
        if (!$this->active) {
            throw new RuntimeException('No active transaction for savepoint');
        }

        $safeName = preg_replace('/[^a-zA-Z0-9_]/', '', $name);
        $this->pdo->exec("SAVEPOINT {$safeName}");
        $this->savepoints[$safeName] = true;
        return true;
    }

    public function rollbackToSavepoint(string $name): bool
    {
        if (!$this->active) {
            throw new RuntimeException('No active transaction');
        }

        $safeName = preg_replace('/[^a-zA-Z0-9_]/', '', $name);
        
        if (!isset($this->savepoints[$safeName])) {
            throw new RuntimeException("Savepoint '{$name}' does not exist");
        }

        $this->pdo->exec("ROLLBACK TO SAVEPOINT {$safeName}");
        return true;
    }

    public function releaseSavepoint(string $name): bool
    {
        if (!$this->active) {
            throw new RuntimeException('No active transaction');
        }

        $safeName = preg_replace('/[^a-zA-Z0-9_]/', '', $name);
        
        if (!isset($this->savepoints[$safeName])) {
            throw new RuntimeException("Savepoint '{$name}' does not exist");
        }

        $this->pdo->exec("RELEASE SAVEPOINT {$safeName}");
        unset($this->savepoints[$safeName]);
        return true;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function execute(callable $callback): mixed
    {
        $this->begin();

        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function getPDO(): \PDO
    {
        return $this->pdo;
    }
}
