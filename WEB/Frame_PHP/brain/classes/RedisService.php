<?php
namespace Services;

use Predis\Client;

class RedisService {
    private $client;
    private $enabled = false;

    public function __construct() {
        $host = getenv('REDIS_HOST') ?: '127.0.0.1';
        $port = (int)(getenv('REDIS_PORT') ?: 6379);
        $password = getenv('REDIS_PASSWORD') ?: null;
        $database = (int)(getenv('REDIS_DB') ?: 0);
        try {
            $params = ['scheme' => 'tcp', 'host' => $host, 'port' => $port, 'database' => $database, 'timeout' => 2.0, 'read_write_timeout' => 2.0, 'connection_timeout' => 2.0];
            if ($password) $params['password'] = $password;
            $this->client = new Client($params);
            $this->client->ping();
            $this->enabled = true;
        } catch (\Throwable $e) {
            $this->enabled = false;
        }
    }

    public function enabled() { return $this->enabled; }

    public function setJson($key, $value, $ttl = 3600) {
        if (!$this->enabled) return false;
        $payload = json_encode($value);
        $this->client->setex($key, $ttl, $payload);
        return true;
    }

    public function getJson($key) {
        if (!$this->enabled) return null;
        $data = $this->client->get($key);
        return $data ? json_decode($data, true) : null;
    }

    public function flush() {
        if (!$this->enabled) return false;
        try {
            $this->client->flushdb();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function deleteByPrefix(string $prefix): int {
        if (!$this->enabled) return 0;
        try {
            $cursor = 0;
            $deleted = 0;
            do {
                $response = $this->client->scan($cursor, ['match' => $prefix . '*', 'count' => 1000]);
                $cursor = (int)($response[0] ?? 0);
                $keys = $response[1] ?? [];
                if (!empty($keys)) {
                    $deleted += $this->client->del($keys);
                }
            } while ($cursor !== 0);
            return $deleted;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function get($key) {
        if (!$this->enabled) return null;
        try {
            return $this->client->get($key);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function setex($key, $ttl, $value) {
        if (!$this->enabled) return false;
        try {
            return $this->client->setex($key, $ttl, $value);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function incr($key) {
        if (!$this->enabled) return false;
        try {
            return $this->client->incr($key);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function expire($key, $ttl) {
        if (!$this->enabled) return false;
        try {
            return $this->client->expire($key, $ttl);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
