<?php

declare(strict_types=1);

class RateLimiter
{
    protected array $attempts = [];
    protected string $storageFile = '';

    public function __construct(?string $storageFile = null)
    {
        if ($storageFile) {
            $this->storageFile = $storageFile;
            $this->loadFromStorage();
        }
    }

    public function attempt(string $key, int $maxAttempts = 5, int $decaySeconds = 60): bool
    {
        $this->cleanup();
        
        if (!$this->tooManyAttempts($key, $maxAttempts)) {
            $this->hit($key, $decaySeconds);
            return true;
        }

        return false;
    }

    public function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        $attempts = $this->attempts($key);
        return $attempts >= $maxAttempts;
    }

    public function hit(string $key, int $decaySeconds = 60): int
    {
        $now = time();
        
        if (!isset($this->attempts[$key])) {
            $this->attempts[$key] = [
                'count' => 0,
                'expires_at' => $now + $decaySeconds
            ];
        }

        if ($this->attempts[$key]['expires_at'] <= $now) {
            $this->attempts[$key] = [
                'count' => 1,
                'expires_at' => $now + $decaySeconds
            ];
        } else {
            $this->attempts[$key]['count']++;
        }

        $this->saveToStorage();
        return $this->attempts[$key]['count'];
    }

    public function attempts(string $key): int
    {
        if (!isset($this->attempts[$key])) {
            return 0;
        }

        if ($this->attempts[$key]['expires_at'] <= time()) {
            return 0;
        }

        return $this->attempts[$key]['count'];
    }

    public function remaining(string $key, int $maxAttempts): int
    {
        return max(0, $maxAttempts - $this->attempts($key));
    }

    public function availableIn(string $key): int
    {
        if (!isset($this->attempts[$key])) {
            return 0;
        }

        return max(0, $this->attempts[$key]['expires_at'] - time());
    }

    public function clear(string $key): void
    {
        unset($this->attempts[$key]);
        $this->saveToStorage();
    }

    public function clearAll(): void
    {
        $this->attempts = [];
        $this->saveToStorage();
    }

    protected function cleanup(): void
    {
        $now = time();
        foreach ($this->attempts as $key => $data) {
            if ($data['expires_at'] <= $now) {
                unset($this->attempts[$key]);
            }
        }
    }

    protected function loadFromStorage(): void
    {
        if ($this->storageFile && file_exists($this->storageFile)) {
            $data = @file_get_contents($this->storageFile);
            if ($data) {
                $this->attempts = json_decode($data, true) ?? [];
            }
        }
    }

    protected function saveToStorage(): void
    {
        if ($this->storageFile) {
            @file_put_contents($this->storageFile, json_encode($this->attempts), LOCK_EX);
        }
    }

    public function __destruct()
    {
        $this->saveToStorage();
    }
}
