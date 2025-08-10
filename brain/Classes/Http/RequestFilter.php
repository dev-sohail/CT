<?php
/**
 * Class RequestFilter
 *
 * A simple HTTP request filter for whitelisting/blacklisting IPs and limiting methods.
 */
class RequestFilter
{
    protected array $allowedIps = [];
    protected array $blockedIps = [];
    protected array $allowedMethods = [];

    public function allowIps(array $ips): void
    {
        $this->allowedIps = $ips;
    }

    public function blockIps(array $ips): void
    {
        $this->blockedIps = $ips;
    }

    public function allowMethods(array $methods): void
    {
        $this->allowedMethods = array_map('strtoupper', $methods);
    }

    public function check(): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? '');

        if (!empty($this->blockedIps) && in_array($ip, $this->blockedIps, true)) {
            return false;
        }

        if (!empty($this->allowedIps) && !in_array($ip, $this->allowedIps, true)) {
            return false;
        }

        if (!empty($this->allowedMethods) && !in_array($method, $this->allowedMethods, true)) {
            return false;
        }

        return true;
    }
}
