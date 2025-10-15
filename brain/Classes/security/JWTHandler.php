<?php

declare(strict_types=1);

class JWTHandler
{
    protected string $secret;
    protected string $algorithm = 'HS256';
    protected int $expiration = 3600;

    public function __construct(string $secret, string $algorithm = 'HS256', int $expiration = 3600)
    {
        if (empty($secret)) {
            throw new InvalidArgumentException('Secret key cannot be empty');
        }

        $this->secret = $secret;
        $this->algorithm = $algorithm;
        $this->expiration = $expiration;
    }

    public function encode(array $payload, ?int $expiration = null): string
    {
        $header = [
            'typ' => 'JWT',
            'alg' => $this->algorithm
        ];

        $payload['iat'] = time();
        $payload['exp'] = time() + ($expiration ?? $this->expiration);

        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));

        $signature = $this->sign($headerEncoded . '.' . $payloadEncoded);

        return $headerEncoded . '.' . $payloadEncoded . '.' . $signature;
    }

    public function decode(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new RuntimeException('Invalid token format');
        }

        [$headerEncoded, $payloadEncoded, $signature] = $parts;

        $header = json_decode($this->base64UrlDecode($headerEncoded), true);
        $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);

        if (!$header || !$payload) {
            throw new RuntimeException('Invalid token data');
        }

        if (!$this->verify($headerEncoded . '.' . $payloadEncoded, $signature)) {
            throw new RuntimeException('Invalid token signature');
        }

        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new RuntimeException('Token has expired');
        }

        return $payload;
    }

    public function verify(string $data, string $signature): bool
    {
        return hash_equals($signature, $this->sign($data));
    }

    public function isExpired(string $token): bool
    {
        try {
            $payload = $this->decode($token);
            return isset($payload['exp']) && $payload['exp'] < time();
        } catch (\Throwable $e) {
            return true;
        }
    }

    public function refresh(string $token, ?int $expiration = null): string
    {
        $payload = $this->decode($token);
        unset($payload['iat'], $payload['exp']);
        return $this->encode($payload, $expiration);
    }

    protected function sign(string $data): string
    {
        switch ($this->algorithm) {
            case 'HS256':
                return $this->base64UrlEncode(hash_hmac('sha256', $data, $this->secret, true));
            
            case 'HS384':
                return $this->base64UrlEncode(hash_hmac('sha384', $data, $this->secret, true));
            
            case 'HS512':
                return $this->base64UrlEncode(hash_hmac('sha512', $data, $this->secret, true));
            
            default:
                throw new RuntimeException("Unsupported algorithm: {$this->algorithm}");
        }
    }

    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    protected function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    public function setExpiration(int $expiration): void
    {
        $this->expiration = $expiration;
    }

    public function setAlgorithm(string $algorithm): void
    {
        $this->algorithm = $algorithm;
    }
}
