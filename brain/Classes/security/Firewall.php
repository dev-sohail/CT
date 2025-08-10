<?php
/**
 * Class Firewall
 *
 * Provides basic IP-based firewall functionality, allowing or blocking requests
 * based on configured allow/deny lists.
 */
class Firewall
{
    protected array $allowedIps = [];
    protected array $blockedIps = [];

    public function __construct(array $allowedIps = [], array $blockedIps = [])
    {
        $this->allowedIps = $allowedIps;
        $this->blockedIps = $blockedIps;
    }

    /**
     * Add an IP address to the allow list.
     */
    public function allow(string $ip): void
    {
        if (!in_array($ip, $this->allowedIps, true)) {
            $this->allowedIps[] = $ip;
        }
    }

    /**
     * Add an IP address to the block list.
     */
    public function block(string $ip): void
    {
        if (!in_array($ip, $this->blockedIps, true)) {
            $this->blockedIps[] = $ip;
        }
    }

    /**
     * Check if the current request is allowed.
     */
    public function isAllowed(?string $ip = null): bool
    {
        $ip = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? '');

        if (in_array($ip, $this->blockedIps, true)) {
            return false;
        }

        if (!empty($this->allowedIps) && !in_array($ip, $this->allowedIps, true)) {
            return false;
        }

        return true;
    }

    /**
     * Enforce firewall rules: deny access if not allowed.
     */
    public function enforce(): void
    {
        if (!$this->isAllowed()) {
            header('HTTP/1.1 403 Forbidden');
            exit('Access denied by firewall.');
        }
    }
}
