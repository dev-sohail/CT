<?php
/**
 * Class LicenseManager
 *
 * Handles generation, validation, and expiration of software licenses.
 */
class LicenseManager
{
    protected string $secretKey;

    public function __construct(string $secretKey)
    {
        $this->secretKey = $secretKey;
    }

    /**
     * Generate a license key for a given user or product ID.
     */
    public function generateKey(string $id, int $validDays = 365): string
    {
        $expiry = time() + ($validDays * 86400);
        $data = $id . '|' . $expiry;
        $hash = hash_hmac('sha256', $data, $this->secretKey);
        return base64_encode($data . '|' . $hash);
    }

    /**
     * Validate a license key.
     */
    public function validateKey(string $key): bool
    {
        $decoded = base64_decode($key, true);
        if ($decoded === false) {
            return false;
        }

        [$id, $expiry, $hash] = explode('|', $decoded, 3) + [null, null, null];
        if (!$id || !$expiry || !$hash) {
            return false;
        }

        if (time() > (int)$expiry) {
            return false; // expired
        }

        $expectedHash = hash_hmac('sha256', $id . '|' . $expiry, $this->secretKey);
        return hash_equals($expectedHash, $hash);
    }
}
