<?php
/**
 * Class Version
 *
 * Handles application versioning with comparison and string parsing.
 */
class Version
{
    protected string $version;

    public function __construct(string $version)
    {
        $this->version = $version;
    }

    /**
     * Get the version string.
     */
    public function get(): string
    {
        return $this->version;
    }

    /**
     * Compare with another version.
     * Returns -1 if older, 0 if equal, 1 if newer.
     */
    public function compare(string $other): int
    {
        return version_compare($this->version, $other);
    }

    /**
     * Check if version is greater than another.
     */
    public function isNewerThan(string $other): bool
    {
        return $this->compare($other) > 0;
    }

    /**
     * Check if version is less than another.
     */
    public function isOlderThan(string $other): bool
    {
        return $this->compare($other) < 0;
    }

    /**
     * Check if version is equal to another.
     */
    public function isEqualTo(string $other): bool
    {
        return $this->compare($other) === 0;
    }
}
