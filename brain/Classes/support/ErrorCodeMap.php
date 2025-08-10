<?php
/**
 * Class ErrorCodeMap
 *
 * Maps error codes to human-readable messages and vice versa.
 */
class ErrorCodeMap
{
    protected array $map = [];

    /**
     * Add or update an error code mapping.
     */
    public function set(int|string $code, string $message): void
    {
        $this->map[$code] = $message;
    }

    /**
     * Get the message for an error code.
     */
    public function getMessage(int|string $code): ?string
    {
        return $this->map[$code] ?? null;
    }

    /**
     * Get the code for a given error message.
     */
    public function getCode(string $message): int|string|null
    {
        return array_search($message, $this->map, true) ?: null;
    }

    /**
     * Check if an error code exists.
     */
    public function hasCode(int|string $code): bool
    {
        return isset($this->map[$code]);
    }

    /**
     * List all error codes and messages.
     */
    public function all(): array
    {
        return $this->map;
    }
}
