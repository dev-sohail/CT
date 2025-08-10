<?php
/**
 * Class TestCase
 *
 * A simple base class for writing and running test cases.
 */
class TestCase
{
    protected array $results = [];

    /**
     * Assert that two values are equal.
     */
    public function assertEquals($expected, $actual, string $message = ''): void
    {
        $this->results[] = [
            'passed' => $expected === $actual,
            'message' => $message ?: "Expected " . var_export($expected, true) . ", got " . var_export($actual, true)
        ];
    }

    /**
     * Assert that a condition is true.
     */
    public function assertTrue($condition, string $message = ''): void
    {
        $this->results[] = [
            'passed' => (bool)$condition === true,
            'message' => $message ?: "Condition is not true"
        ];
    }

    /**
     * Assert that a condition is false.
     */
    public function assertFalse($condition, string $message = ''): void
    {
        $this->results[] = [
            'passed' => (bool)$condition === false,
            'message' => $message ?: "Condition is not false"
        ];
    }

    /**
     * Get all test results.
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Print a test report.
     */
    public function report(): void
    {
        foreach ($this->results as $index => $result) {
            echo ($result['passed'] ? '[PASS] ' : '[FAIL] ') . ($result['message'] ?: '') . PHP_EOL;
        }
    }
}
