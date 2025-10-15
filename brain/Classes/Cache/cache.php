<?php

declare(strict_types=1);

class Cache
{
    private static ?Cache $instance = null;
    private string $driver = 'file';
    private string $cachePath;
    private int $defaultTTL = 3600;
    private array $memoryCache = [];
    private array $stats = ['hits' => 0, 'misses' => 0, 'writes' => 0, 'deletes' => 0];

    public function __construct(array $config = [])
    {
        $this->driver = $config['driver'] ?? getenv('CACHE_DRIVER') ?: 'file';
        $this->defaultTTL = (int)($config['ttl'] ?? getenv('CACHE_TTL') ?: 3600);
        
        $root = defined('ROOT') ? ROOT : __DIR__ . '/../../..';
        $this->cachePath = $config['path'] ?? $root . '/Storage/cache';
        
        if (!file_exists($this->cachePath)) {
            @mkdir($this->cachePath, 0755, true);
        }
    }

    public static function getInstance(array $config = []): Cache
    {
        if (class_exists('Registry', false)) {
            $registry = Registry::getInstance();
            if ($registry->has('cache')) {
                return $registry->get('cache');
            }
        }

        if (self::$instance === null) {
            self::$instance = new self($config);
            if (class_exists('Registry', false)) {
                Registry::getInstance()->set('cache', self::$instance);
            }
        }
        return self::$instance;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        return $this->put($key, $value, $ttl);
    }

    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTTL;
        $cacheKey = $this->generateKey($key);
        
        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl,
            'created_at' => time(),
        ];

        $this->stats['writes']++;

        switch ($this->driver) {
            case 'memory':
                $this->memoryCache[$cacheKey] = $data;
                return true;

            case 'file':
            default:
                $file = $this->cachePath . '/' . $cacheKey . '.cache';
                return file_put_contents($file, serialize($data), LOCK_EX) !== false;
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = $this->generateKey($key);

        switch ($this->driver) {
            case 'memory':
                if (!isset($this->memoryCache[$cacheKey])) {
                    $this->stats['misses']++;
                    return $default;
                }
                $data = $this->memoryCache[$cacheKey];
                break;

            case 'file':
            default:
                $file = $this->cachePath . '/' . $cacheKey . '.cache';
                if (!file_exists($file)) {
                    $this->stats['misses']++;
                    return $default;
                }
                $data = @unserialize(file_get_contents($file));
                if ($data === false) {
                    $this->stats['misses']++;
                    return $default;
                }
                break;
        }

        if ($data['expires_at'] < time()) {
            $this->delete($key);
            $this->stats['misses']++;
            return $default;
        }

        $this->stats['hits']++;
        return $data['value'];
    }

    public function has(string $key): bool
    {
        return $this->get($key, '__CACHE_MISS__') !== '__CACHE_MISS__';
    }

    public function delete(string $key): bool
    {
        $cacheKey = $this->generateKey($key);
        $this->stats['deletes']++;

        switch ($this->driver) {
            case 'memory':
                unset($this->memoryCache[$cacheKey]);
                return true;

            case 'file':
            default:
                $file = $this->cachePath . '/' . $cacheKey . '.cache';
                return file_exists($file) ? unlink($file) : false;
        }
    }

    public function flush(): bool
    {
        switch ($this->driver) {
            case 'memory':
                $this->memoryCache = [];
                return true;

            case 'file':
            default:
                $files = glob($this->cachePath . '/*.cache');
                foreach ($files as $file) {
                    @unlink($file);
                }
                return true;
        }
    }

    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $value = $this->get($key);
        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }

    public function forever(string $key, mixed $value): bool
    {
        return $this->set($key, $value, 315360000); // 10 years
    }

    public function getStats(): array
    {
        return $this->stats;
    }

    private function generateKey(string $key): string
    {
        return md5($key);
    }
}
