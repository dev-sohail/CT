<?php
/**
 * Class Mocker
 *
 * A minimal mocking utility to simulate objects and methods for testing.
 */
class Mocker
{
    protected array $mocks = [];

    /**
     * Create a mock object with specified method return values.
     */
    public function create(array $methods): object
    {
        $mock = new class($methods) {
            private array $methods;

            public function __construct(array $methods)
            {
                $this->methods = $methods;
            }

            public function __call($name, $arguments)
            {
                if (array_key_exists($name, $this->methods)) {
                    $return = $this->methods[$name];
                    return is_callable($return) ? $return(...$arguments) : $return;
                }
                throw new \BadMethodCallException("Method $name not mocked");
            }
        };

        $this->mocks[] = $mock;
        return $mock;
    }

    /**
     * Clear all stored mocks.
     */
    public function clear(): void
    {
        $this->mocks = [];
    }

    /**
     * Get all mocks for inspection.
     */
    public function all(): array
    {
        return $this->mocks;
    }
}
