<?php
namespace Services;

class CacheService {
    private ?RedisService $redis = null;

    public function __construct() {
        $this->redis = new RedisService();
    }

    public function get(string $key, $default = null) {
        if (!$this->redis->enabled()) return $default;
        $data = $this->redis->getJson($this->prefix($key));
        return $data !== null ? $data : $default;
    }

    public function put(string $key, $value, int $ttl = 600): bool {
        if (!$this->redis->enabled()) return false;
        return $this->redis->setJson($this->prefix($key), $value, $ttl);
    }

    public function forget(string $key): bool {
        if (!$this->redis->enabled()) return false;
        return $this->redis->deleteByPrefix($this->prefix($key)) > 0;
    }

    public function flush(): bool {
        if (!$this->redis->enabled()) return false;
        return $this->redis->flush();
    }

    private function prefix(string $key): string {
        return 'cache:' . $key;
    }
}
