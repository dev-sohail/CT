<?php

declare(strict_types=1);

class PasswordHasher
{
    protected string $algorithm = PASSWORD_BCRYPT;
    protected array $options = ['cost' => 12];

    public function __construct(string $algorithm = PASSWORD_BCRYPT, array $options = [])
    {
        $this->algorithm = $algorithm;
        $this->options = array_merge($this->options, $options);
    }

    public function hash(string $password): string
    {
        if (empty($password)) {
            throw new InvalidArgumentException('Password cannot be empty');
        }

        $hash = password_hash($password, $this->algorithm, $this->options);
        
        if ($hash === false) {
            throw new RuntimeException('Password hashing failed');
        }

        return $hash;
    }

    public function verify(string $password, string $hash): bool
    {
        if (empty($password) || empty($hash)) {
            return false;
        }

        return password_verify($password, $hash);
    }

    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, $this->algorithm, $this->options);
    }

    public function rehash(string $password, string $oldHash): ?string
    {
        if ($this->verify($password, $oldHash) && $this->needsRehash($oldHash)) {
            return $this->hash($password);
        }

        return null;
    }

    public function getInfo(string $hash): array
    {
        return password_get_info($hash);
    }

    public static function make(string $password, string $algorithm = PASSWORD_BCRYPT, array $options = []): string
    {
        $hasher = new self($algorithm, $options);
        return $hasher->hash($password);
    }

    public static function check(string $password, string $hash): bool
    {
        $hasher = new self();
        return $hasher->verify($password, $hash);
    }

    public function setAlgorithm(string $algorithm): void
    {
        $this->algorithm = $algorithm;
    }

    public function setOptions(array $options): void
    {
        $this->options = array_merge($this->options, $options);
    }

    public function setCost(int $cost): void
    {
        if ($cost < 4 || $cost > 31) {
            throw new InvalidArgumentException('Cost must be between 4 and 31');
        }

        $this->options['cost'] = $cost;
    }
}
