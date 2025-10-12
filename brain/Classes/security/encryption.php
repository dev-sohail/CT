<?php

declare(strict_types=1);

/**
 * Class Encryption
 *
 * Handles encryption, decryption, and hashing.
 *
 * Features:
 * - AES-256-CBC encryption/decryption
 * - SHA256 hashing
 * - Password hashing and verification (BCrypt)
 * - Random token generation
 */
class Encryption
{
    private string $key;
    private string $method = 'AES-256-CBC';

    public function __construct(string $key = '')
    {
        if (empty($key)) {
            $key = defined('ENCRYPTION_KEY') ? ENCRYPTION_KEY : 'CT_Framework_Encryption_Key';
        }

        $this->key = hash('sha256', $key, true); // 32-byte key for AES-256
    }

    /**
     * Encrypt a string
     * 
     * @param string $data Data to encrypt
     * @return string Encrypted data
     * @throws RuntimeException If encryption fails
     */
    public function encrypt(string $data): string
    {
        $ivLength = openssl_cipher_iv_length($this->method);
        if ($ivLength === false) {
            throw new RuntimeException('Unable to get IV length for cipher method');
        }
        
        $iv = random_bytes($ivLength);
        $encrypted = openssl_encrypt($data, $this->method, $this->key, OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            throw new RuntimeException('Encryption failed');
        }

        // Combine IV + encrypted data for storage
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt a string
     * 
     * @param string $data Encrypted data
     * @return string Decrypted data
     * @throws RuntimeException If decryption fails
     */
    public function decrypt(string $data): string
    {
        $raw = base64_decode($data);
        if ($raw === false) {
            throw new RuntimeException('Invalid base64 data');
        }
        
        $ivLength = openssl_cipher_iv_length($this->method);
        if ($ivLength === false) {
            throw new RuntimeException('Unable to get IV length for cipher method');
        }
        
        $iv = substr($raw, 0, $ivLength);
        $encrypted = substr($raw, $ivLength);

        $decrypted = openssl_decrypt($encrypted, $this->method, $this->key, OPENSSL_RAW_DATA, $iv);

        if ($decrypted === false) {
            throw new RuntimeException('Decryption failed');
        }

        return $decrypted;
    }

    /**
     * Generate a secure random token
     * 
     * @param int $length Token length
     * @return string Random token
     */
    public function token(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * SHA256 hash (e.g. for data integrity)
     * 
     * @param string $data Data to hash
     * @return string Hash
     */
    public function hash(string $data): string
    {
        return hash('sha256', $data);
    }

    /**
     * Create a secure password hash (BCrypt)
     * 
     * @param string $password Password to hash
     * @return string Password hash
     */
    public function password(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Verify password hash
     * 
     * @param string $password Password to verify
     * @param string $hash Hash to verify against
     * @return bool True if password matches
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Compare two hashes securely (constant-time)
     * 
     * @param string $a First hash
     * @param string $b Second hash
     * @return bool True if hashes match
     */
    public function secureCompare(string $a, string $b): bool
    {
        return hash_equals($a, $b);
    }

    /**
     * Generate a UUID v4
     * 
     * @return string UUID
     */
    public function generateUUID(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Get current encryption method
     * 
     * @return string Encryption method
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Set encryption method
     * 
     * @param string $method New encryption method
     */
    public function setMethod(string $method): void
    {
        $this->method = $method;
    }
}
