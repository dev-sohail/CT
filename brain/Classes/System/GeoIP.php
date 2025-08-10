<?php
/**
 * Class GeoIP
 *
 * A simple GeoIP lookup utility using external APIs or local databases.
 */
class GeoIP
{
    protected string $serviceUrl = 'https://freegeoip.app/json/';

    /**
     * Lookup IP address details.
     */
    public function lookup(string $ip = ''): ?array
    {
        if (empty($ip)) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        }

        $url = rtrim($this->serviceUrl, '/') . '/' . $ip;

        $context = stream_context_create([
            'http' => [
                'timeout' => 5
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);
        return is_array($data) ? $data : null;
    }

    /**
     * Set the GeoIP service URL.
     */
    public function setServiceUrl(string $url): void
    {
        $this->serviceUrl = $url;
    }

    /**
     * Get the currently configured service URL.
     */
    public function getServiceUrl(): string
    {
        return $this->serviceUrl;
    }
}
