<?php
/**
 * Class RateLimiter
 *
 * A simple in-memory rate limiter.
 */
class RateLimiter
{
    protected array $attempts = [];

    /**
     * Check if a given key is allowed based on max attempts and time window.
     */
    public function allow(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        $now = time();

        // Initialize if not set
        if (!isset($this->attempts[$key])) {
            $this->attempts[$key] = [
                'count' => 0,
                'expires_at' => $now + $decaySeconds
            ];
        }

        // Reset if expired
        if ($this->attempts[$key]['expires_at'] <= $now) {
            $this->attempts[$key] = [
                'count' => 0,
                'expires_at' => $now + $decaySeconds
            ];
        }

        // Check limit
        if ($this->attempts[$key]['count'] < $maxAttempts) {
            $this->attempts[$key]['count']++;
            return true;
        }

        return false;
    }

    /**
     * Get remaining attempts for a given key.
     */
    public function remaining(string $key, int $maxAttempts): int
    {
        return isset($this->attempts[$key])
            ? max(0, $maxAttempts - $this->attempts[$key]['count'])
            : $maxAttempts;
    }

    /**
     * Get seconds until the limit resets.
     */
    public function retryAfter(string $key): int
    {
        return isset($this->attempts[$key])
            ? max(0, $this->attempts[$key]['expires_at'] - time())
            : 0;
    }
}
