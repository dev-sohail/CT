<?php

/**
 * Enhanced API Class with New Features:
 * - APIAuth
 * - APIDocsGenerator
 * - APIRateLimiter
 * - APIRequest
 * - APIResponse & Formatter
 * - APIVersioning
 * - HttpClient
 * - JSON Helpers
 * - ServiceDiscovery
 * Compatible with REST API, FastAPI, etc.
 */

class API
{
    /**
     * Send a JSON response with given data and status code.
     */
    public static function jsonResponse($data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Retrieve input JSON as an associative array.
     */
    public static function getJsonInput(): array
    {
        $input = file_get_contents('php://input');
        $decoded = json_decode($input, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Send an error message in JSON format.
     */
    public static function sendError(string $message, int $statusCode = 400): void
    {
        self::jsonResponse(['error' => $message], $statusCode);
    }

    /**
     * Send a standardized success response.
     */
    public static function sendSuccess($data, string $message = 'Success', int $statusCode = 200): void
    {
        self::jsonResponse([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * API Authentication via API key.
     */
    public static function authenticate(string $providedKey, string $validKey): bool
    {
        if ($providedKey !== $validKey) {
            self::sendError('Unauthorized', 401);
            return false;
        }
        return true;
    }

    /**
     * Apply simple IP-based rate limiting.
     */
    public static function rateLimit(string $clientId, int $maxRequests, int $timeWindowSeconds): bool
    {
        $key = "rate_limit_{$clientId}";
        $current = apcu_fetch($key);

        if ($current === false) {
            apcu_store($key, 1, $timeWindowSeconds);
            return true;
        }

        if ($current >= $maxRequests) {
            self::sendError('Too Many Requests', 429);
            return false;
        }

        apcu_inc($key);
        return true;
    }

    /**
     * Generate API Documentation in JSON format.
     */
    public static function generateDocs(array $endpoints): void
    {
        self::jsonResponse([
            'api_version' => self::getVersion(),
            'endpoints' => $endpoints
        ]);
    }

    /**
     * Get API version from request.
     */
    public static function getVersion(): string
    {
        return $_GET['version'] ?? '0.1';
    }

    /**
     * Perform a GET request to an external service.
     */
    public static function httpGet(string $url): ?array
    {
        $response = file_get_contents($url);
        return $response ? json_decode($response, true) : null;
    }

    /**
     * Service discovery simulation (placeholder).
     */
    public static function discoverService(string $serviceName): ?string
    {
        $services = [
            'user' => 'https://api.example.com/users',
            'order' => 'https://api.example.com/orders',
        ];
        return $services[$serviceName] ?? null;
    }
}
