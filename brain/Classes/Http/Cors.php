<?php

declare(strict_types=1);

/**
 * Class Cors
 *
 * Handles Cross-Origin Resource Sharing (CORS) headers with comprehensive configuration.
 */
class Cors
{
    protected array $allowedOrigins;
    protected array $allowedMethods;
    protected array $allowedHeaders;
    protected array $exposedHeaders;
    protected bool $allowCredentials;
    protected int $maxAge;

    public function __construct(
        array $allowedOrigins = ['*'],
        array $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
        array $allowedHeaders = ['Content-Type', 'Authorization', 'X-Requested-With'],
        array $exposedHeaders = [],
        bool $allowCredentials = true,
        int $maxAge = 86400
    ) {
        $this->allowedOrigins = $allowedOrigins;
        $this->allowedMethods = $allowedMethods;
        $this->allowedHeaders = $allowedHeaders;
        $this->exposedHeaders = $exposedHeaders;
        $this->allowCredentials = $allowCredentials;
        $this->maxAge = $maxAge;
    }

    /**
     * Send CORS headers.
     */
    public function sendHeaders(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if ($this->isOriginAllowed($origin)) {
            $allowOrigin = in_array('*', $this->allowedOrigins, true) ? '*' : $origin;
            header('Access-Control-Allow-Origin: ' . $allowOrigin);

            if (!empty($this->allowedMethods)) {
                header('Access-Control-Allow-Methods: ' . implode(', ', $this->allowedMethods));
            }

            if (!empty($this->allowedHeaders)) {
                header('Access-Control-Allow-Headers: ' . implode(', ', $this->allowedHeaders));
            }

            if (!empty($this->exposedHeaders)) {
                header('Access-Control-Expose-Headers: ' . implode(', ', $this->exposedHeaders));
            }

            if ($this->allowCredentials && $allowOrigin !== '*') {
                header('Access-Control-Allow-Credentials: true');
            }

            if ($this->maxAge > 0) {
                header('Access-Control-Max-Age: ' . $this->maxAge);
            }
        }
    }

    /**
     * Check if origin is allowed.
     */
    protected function isOriginAllowed(string $origin): bool
    {
        if (empty($origin)) {
            return false;
        }

        if (in_array('*', $this->allowedOrigins, true)) {
            return true;
        }

        if (in_array($origin, $this->allowedOrigins, true)) {
            return true;
        }

        foreach ($this->allowedOrigins as $allowed) {
            if (fnmatch($allowed, $origin)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle preflight (OPTIONS) requests.
     */
    public function handlePreflight(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            $this->sendHeaders();
            http_response_code(204);
            header('Content-Length: 0');
            header('Content-Type: text/plain');
            exit;
        }
    }

    /**
     * Add allowed origin.
     */
    public function addAllowedOrigin(string $origin): void
    {
        if (!in_array($origin, $this->allowedOrigins, true)) {
            $this->allowedOrigins[] = $origin;
        }
    }

    /**
     * Add allowed method.
     */
    public function addAllowedMethod(string $method): void
    {
        $method = strtoupper($method);
        if (!in_array($method, $this->allowedMethods, true)) {
            $this->allowedMethods[] = $method;
        }
    }

    /**
     * Add allowed header.
     */
    public function addAllowedHeader(string $header): void
    {
        if (!in_array($header, $this->allowedHeaders, true)) {
            $this->allowedHeaders[] = $header;
        }
    }

    /**
     * Add exposed header.
     */
    public function addExposedHeader(string $header): void
    {
        if (!in_array($header, $this->exposedHeaders, true)) {
            $this->exposedHeaders[] = $header;
        }
    }
}
