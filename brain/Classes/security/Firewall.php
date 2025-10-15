<?php

declare(strict_types=1);

/**
 * Class Firewall
 *
 * Provides comprehensive IP-based firewall functionality, allowing or blocking requests
 * based on configured allow/deny lists with CIDR support.
 */
class Firewall
{
    protected array $allowedIps = [];
    protected array $blockedIps = [];
    protected array $requestLog = [];
    protected ?RateLimiter $rateLimiter = null;
    protected bool $logging = false;

    public function __construct(array $allowedIps = [], array $blockedIps = [], bool $logging = false)
    {
        $this->allowedIps = $allowedIps;
        $this->blockedIps = $blockedIps;
        $this->logging = $logging;
    }

    /**
     * Add an IP address or CIDR to the allow list.
     */
    public function allow(string $ip): void
    {
        if (!in_array($ip, $this->allowedIps, true)) {
            $this->allowedIps[] = $ip;
        }
    }

    /**
     * Add an IP address or CIDR to the block list.
     */
    public function block(string $ip): void
    {
        if (!in_array($ip, $this->blockedIps, true)) {
            $this->blockedIps[] = $ip;
        }
    }

    /**
     * Remove an IP from the block list.
     */
    public function unblock(string $ip): void
    {
        $this->blockedIps = array_filter($this->blockedIps, fn($blocked) => $blocked !== $ip);
    }

    /**
     * Check if an IP matches a CIDR range.
     */
    protected function matchCIDR(string $ip, string $cidr): bool
    {
        if (!str_contains($cidr, '/')) {
            return $ip === $cidr;
        }

        [$subnet, $mask] = explode('/', $cidr);
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - (int)$mask);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }

    /**
     * Check if IP is in a list (supports CIDR).
     */
    protected function inList(string $ip, array $list): bool
    {
        foreach ($list as $entry) {
            if ($this->matchCIDR($ip, $entry)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if the current request is allowed.
     */
    public function isAllowed(?string $ip = null): bool
    {
        $ip = $ip ?? $this->getClientIp();

        if ($this->inList($ip, $this->blockedIps)) {
            $this->log('blocked', $ip);
            return false;
        }

        if (!empty($this->allowedIps) && !$this->inList($ip, $this->allowedIps)) {
            $this->log('not_allowed', $ip);
            return false;
        }

        if ($this->rateLimiter && !$this->rateLimiter->check($ip)) {
            $this->log('rate_limited', $ip);
            return false;
        }

        $this->log('allowed', $ip);
        return true;
    }

    /**
     * Enforce firewall rules: deny access if not allowed.
     */
    public function enforce(): void
    {
        if (!$this->isAllowed()) {
            http_response_code(403);
            header('HTTP/1.1 403 Forbidden');
            exit('Access denied by firewall.');
        }
    }

    /**
     * Get client IP address (handles proxies).
     */
    protected function getClientIp(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (str_contains($ip, ',')) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Set rate limiter for additional protection.
     */
    public function setRateLimiter(RateLimiter $rateLimiter): void
    {
        $this->rateLimiter = $rateLimiter;
    }

    /**
     * Log firewall events.
     */
    protected function log(string $action, string $ip): void
    {
        if (!$this->logging) {
            return;
        }

        $this->requestLog[] = [
            'timestamp' => time(),
            'action' => $action,
            'ip' => $ip,
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? ''
        ];
    }

    /**
     * Get firewall log.
     */
    public function getLog(): array
    {
        return $this->requestLog;
    }

    /**
     * Clear firewall log.
     */
    public function clearLog(): void
    {
        $this->requestLog = [];
    }
}
