<?php
/**
 * Class UnitTest
 *
 * A simple unit testing utility for executing test cases and reporting results.
 */
class UnitTest
{
    protected array $results = [];

    /**
     * Run a test with a description.
     */
    public function run(string $description, callable $test): void
    {
        try {
            $test();
            $this->results[] = [
                'description' => $description,
                'status'      => 'passed'
            ];
        } catch (\Throwable $e) {
            $this->results[] = [
                'description' => $description,
                'status'      => 'failed',
                'message'     => $e->getMessage()
            ];
        }
    }

    /**
     * Assert that two values are equal.
     */
    public function assertEquals($expected, $actual): void
    {
        if ($expected !== $actual) {
            throw new \Exception("Expected " . var_export($expected, true) . ", got " . var_export($actual, true));
        }
    }

    /**
     * Get all test results.
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Display a summary of results.
     */
    public function report(): void
    {
        foreach ($this->results as $result) {
            echo "[{$result['status']}] {$result['description']}";
            if (isset($result['message'])) {
                echo " - {$result['message']}";
            }
            echo PHP_EOL;
        }
    }
}
