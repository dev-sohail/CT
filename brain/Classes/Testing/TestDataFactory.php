<?php
/**
 * Class TestDataFactory
 *
 * A utility for generating test data objects and arrays with configurable defaults.
 */
class TestDataFactory
{
    protected array $defaults = [];

    public function __construct(array $defaults = [])
    {
        $this->defaults = $defaults;
    }

    /**
     * Create a test data array with optional overrides.
     */
    public function make(array $overrides = []): array
    {
        return array_merge($this->defaults, $overrides);
    }

    /**
     * Create multiple sets of test data.
     */
    public function makeMany(int $count, array $overrides = []): array
    {
        $data = [];
        for ($i = 0; $i < $count; $i++) {
            $data[] = $this->make($overrides);
        }
        return $data;
    }

    /**
     * Set default values for generated test data.
     */
    public function setDefaults(array $defaults): void
    {
        $this->defaults = $defaults;
    }

    /**
     * Get the current default values.
     */
    public function getDefaults(): array
    {
        return $this->defaults;
    }
}
