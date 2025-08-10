<?php
/**
 * Class Cors
 *
 * Handles Cross-Origin Resource Sharing (CORS) headers.
 */
class Cors
{
    protected array $allowedOrigins;
    protected array $allowedMethods;
    protected array $allowedHeaders;
    protected bool $allowCredentials;

    public function __construct(
        array $allowedOrigins = ['*'],
        array $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        array $allowedHeaders = ['Content-Type', 'Authorization'],
        bool $allowCredentials = true
    ) {
        $this->allowedOrigins = $allowedOrigins;
        $this->allowedMethods = $allowedMethods;
        $this->allowedHeaders = $allowedHeaders;
        $this->allowCredentials = $allowCredentials;
    }

    /**
     * Send CORS headers.
     */
    public function sendHeaders(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (in_array('*', $this->allowedOrigins) || in_array($origin, $this->allowedOrigins)) {
            header('Access-Control-Allow-Origin: ' . (in_array('*', $this->allowedOrigins) ? '*' : $origin));
        }

        header('Access-Control-Allow-Methods: ' . implode(', ', $this->allowedMethods));
        header('Access-Control-Allow-Headers: ' . implode(', ', $this->allowedHeaders));

        if ($this->allowCredentials) {
            header('Access-Control-Allow-Credentials: true');
        }
    }

    /**
     * Handle preflight (OPTIONS) requests.
     */
    public function handlePreflight(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            $this->sendHeaders();
            http_response_code(204);
            exit;
        }
    }
}
