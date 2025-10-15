<?php

declare(strict_types=1);

class Session
{
    public array $data = [];
    protected array $config = [
        'check_ip' => true,
        'check_user_agent' => true,
        'regenerate_time' => 300,
    ];

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);

        if (!session_id() && !headers_sent()) {
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

            session_set_cookie_params([
                'lifetime' => (int)(getenv('SESSION_LIFETIME') ?: 7200),
                'path' => '/',
                'domain' => '',
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            session_name(getenv('SESSION_NAME') ?: 'CAFSESSID');
            session_start();
        }

        $this->data =& $_SESSION;
        $this->initializeSecurity();
    }

    public function start(): void
    {
        if (!session_id()) {
            session_start();
            $this->data =& $_SESSION;
        }
    }

    public function getId(): string
    {
        return session_id();
    }

    public function destroy(): void
    {
        if (session_id()) {
            session_unset();
            session_destroy();
            $this->data = [];
        }
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    public function flash(string $key, mixed $value): void
    {
        $this->data['_flash'][$key] = $value;
    }

    public function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $this->data['_flash'][$key] ?? $default;
        unset($this->data['_flash'][$key]);
        return $value;
    }

    public function regenerate(bool $deleteOld = true): bool
    {
        if (session_regenerate_id($deleteOld)) {
            $this->data['_secure']['last_regen'] = time();
            return true;
        }
        return false;
    }

    protected function initializeSecurity(): void
    {
        if (!isset($this->data['_secure'])) {
            $this->data['_secure'] = [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'created' => time(),
                'last_regen' => time(),
            ];
        }

        if (!$this->isValid()) {
            $this->destroy();
            session_regenerate_id(true);
            $this->start();
            $this->initializeSecurity();
        } elseif ($this->shouldRegenerate()) {
            $this->regenerate();
        }
    }

    protected function isValid(): bool
    {
        if ($this->config['check_ip']) {
            $currentIp = $_SERVER['REMOTE_ADDR'] ?? '';
            if ($this->data['_secure']['ip'] !== $currentIp) {
                return false;
            }
        }

        if ($this->config['check_user_agent']) {
            $currentUA = $_SERVER['HTTP_USER_AGENT'] ?? '';
            if ($this->data['_secure']['user_agent'] !== $currentUA) {
                return false;
            }
        }

        return true;
    }

    protected function shouldRegenerate(): bool
    {
        $lastRegen = $this->data['_secure']['last_regen'] ?? 0;
        return (time() - $lastRegen) > $this->config['regenerate_time'];
    }

    public function all(): array
    {
        return $this->data;
    }

    public function clear(): void
    {
        $secure = $this->data['_secure'] ?? [];
        $this->data = ['_secure' => $secure];
    }

    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->set($key, $value);
    }

    public function __isset(string $key): bool
    {
        return $this->has($key);
    }

    public function __unset(string $key): void
    {
        $this->remove($key);
    }
}
