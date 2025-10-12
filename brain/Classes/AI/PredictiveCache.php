<?php

declare(strict_types=1);

/**
 * PredictiveCache Class
 * 
 * Implements predictive caching using machine learning patterns
 */
class PredictiveCache
{
    private array $cache = [];
    private array $accessPatterns = [];
    private array $predictionModel = [];
    private int $maxCacheSize = 1000;
    private float $hitRate = 0.0;
    private int $totalRequests = 0;
    private int $cacheHits = 0;

    /**
     * Constructor
     * 
     * @param int $maxCacheSize Maximum cache size
     */
    public function __construct(int $maxCacheSize = 1000)
    {
        $this->maxCacheSize = $maxCacheSize;
    }

    /**
     * Get item from cache
     * 
     * @param string $key Cache key
     * @return mixed Cached value or null
     */
    public function get(string $key): mixed
    {
        $this->totalRequests++;
        
        if (isset($this->cache[$key])) {
            $this->cacheHits++;
            $this->recordAccess($key);
            $this->updateHitRate();
            return $this->cache[$key];
        }

        // Predict and preload related items
        $this->predictAndPreload($key);
        
        return null;
    }

    /**
     * Set item in cache
     * 
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $ttl Time to live in seconds
     */
    public function set(string $key, mixed $value, int $ttl = 3600): void
    {
        // Check cache size limit
        if (count($this->cache) >= $this->maxCacheSize) {
            $this->evictLeastUsed();
        }

        $this->cache[$key] = [
            'value' => $value,
            'timestamp' => time(),
            'ttl' => $ttl,
            'access_count' => 0
        ];

        $this->recordAccess($key);
    }

    /**
     * Check if key exists in cache
     * 
     * @param string $key Cache key
     * @return bool True if exists and not expired
     */
    public function has(string $key): bool
    {
        if (!isset($this->cache[$key])) {
            return false;
        }

        $item = $this->cache[$key];
        if (time() - $item['timestamp'] > $item['ttl']) {
            unset($this->cache[$key]);
            return false;
        }

        return true;
    }

    /**
     * Delete item from cache
     * 
     * @param string $key Cache key
     */
    public function delete(string $key): void
    {
        unset($this->cache[$key]);
    }

    /**
     * Clear all cache
     */
    public function clear(): void
    {
        $this->cache = [];
        $this->accessPatterns = [];
        $this->predictionModel = [];
        $this->totalRequests = 0;
        $this->cacheHits = 0;
        $this->hitRate = 0.0;
    }

    /**
     * Record access pattern
     * 
     * @param string $key Accessed key
     */
    private function recordAccess(string $key): void
    {
        $timestamp = time();
        
        if (!isset($this->accessPatterns[$key])) {
            $this->accessPatterns[$key] = [];
        }

        $this->accessPatterns[$key][] = $timestamp;
        
        // Keep only recent access patterns (last 100)
        if (count($this->accessPatterns[$key]) > 100) {
            array_shift($this->accessPatterns[$key]);
        }

        // Update access count in cache
        if (isset($this->cache[$key])) {
            $this->cache[$key]['access_count']++;
        }
    }

    /**
     * Predict and preload related items
     * 
     * @param string $key Current key
     */
    private function predictAndPreload(string $key): void
    {
        // Simple prediction based on access patterns
        $relatedKeys = $this->findRelatedKeys($key);
        
        foreach ($relatedKeys as $relatedKey) {
            if (!isset($this->cache[$relatedKey])) {
                // Trigger preload callback if available
                if (isset($this->predictionModel['preload_callback'])) {
                    $value = call_user_func($this->predictionModel['preload_callback'], $relatedKey);
                    if ($value !== null) {
                        $this->set($relatedKey, $value);
                    }
                }
            }
        }
    }

    /**
     * Find related keys based on access patterns
     * 
     * @param string $key Current key
     * @return array Related keys
     */
    private function findRelatedKeys(string $key): array
    {
        $relatedKeys = [];
        
        // Find keys that are often accessed together
        foreach ($this->accessPatterns as $otherKey => $accesses) {
            if ($otherKey === $key) {
                continue;
            }

            $correlation = $this->calculateCorrelation($key, $otherKey);
            if ($correlation > 0.7) { // 70% correlation threshold
                $relatedKeys[] = $otherKey;
            }
        }

        return array_slice($relatedKeys, 0, 5); // Limit to 5 related keys
    }

    /**
     * Calculate correlation between two keys
     * 
     * @param string $key1 First key
     * @param string $key2 Second key
     * @return float Correlation score (0-1)
     */
    private function calculateCorrelation(string $key1, string $key2): float
    {
        if (!isset($this->accessPatterns[$key1]) || !isset($this->accessPatterns[$key2])) {
            return 0.0;
        }

        $accesses1 = $this->accessPatterns[$key1];
        $accesses2 = $this->accessPatterns[$key2];

        // Simple correlation based on access frequency
        $totalAccesses = count($accesses1) + count($accesses2);
        if ($totalAccesses === 0) {
            return 0.0;
        }

        $commonAccesses = count(array_intersect($accesses1, $accesses2));
        return $commonAccesses / min(count($accesses1), count($accesses2));
    }

    /**
     * Evict least used items
     */
    private function evictLeastUsed(): void
    {
        if (empty($this->cache)) {
            return;
        }

        // Sort by access count and timestamp
        uasort($this->cache, function($a, $b) {
            if ($a['access_count'] === $b['access_count']) {
                return $a['timestamp'] - $b['timestamp'];
            }
            return $a['access_count'] - $b['access_count'];
        });

        // Remove the first (least used) item
        $keys = array_keys($this->cache);
        unset($this->cache[$keys[0]]);
    }

    /**
     * Update hit rate
     */
    private function updateHitRate(): void
    {
        if ($this->totalRequests > 0) {
            $this->hitRate = $this->cacheHits / $this->totalRequests;
        }
    }

    /**
     * Set preload callback
     * 
     * @param callable $callback Preload callback function
     */
    public function setPreloadCallback(callable $callback): void
    {
        $this->predictionModel['preload_callback'] = $callback;
    }

    /**
     * Get cache statistics
     * 
     * @return array Cache statistics
     */
    public function getStats(): array
    {
        return [
            'total_requests' => $this->totalRequests,
            'cache_hits' => $this->cacheHits,
            'hit_rate' => $this->hitRate,
            'cache_size' => count($this->cache),
            'max_cache_size' => $this->maxCacheSize,
            'memory_usage' => memory_get_usage(true)
        ];
    }

    /**
     * Get cache keys
     * 
     * @return array Cache keys
     */
    public function getKeys(): array
    {
        return array_keys($this->cache);
    }

    /**
     * Get cache size
     * 
     * @return int Cache size
     */
    public function getSize(): int
    {
        return count($this->cache);
    }

    /**
     * Check if cache is full
     * 
     * @return bool True if cache is full
     */
    public function isFull(): bool
    {
        return count($this->cache) >= $this->maxCacheSize;
    }
}