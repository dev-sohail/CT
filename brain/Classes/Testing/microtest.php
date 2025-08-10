<?php
/**
 * Class MicroTest
 *
 * A super lightweight testing utility for simple assertions and test case tracking.
 */
class MicroTest
{
    protected array $tests = [];

    /**
     * Define and run a test case.
     */
    public function test(string $description, callable $callback): void
    {
        try {
            $callback();
            $this->tests[] = [
                'description' => $description,
                'status' => 'PASS'
            ];
        } catch (\Throwable $e) {
            $this->tests[] = [
                'description' => $description,
                'status' => 'FAIL',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Assert equality.
     */
    public function assertEquals($expected, $actual, string $message = ''): void
    {
        if ($expected != $actual) {
            throw new \Exception($message ?: "Expected $expected but got $actual");
        }
    }

    /**
     * Assert truthiness.
     */
    public function assertTrue($condition, string $message = ''): void
    {
        if (!$condition) {
            throw new \Exception($message ?: "Condition is not true");
        }
    }

    /**
     * Output results.
     */
    public function report(): void
    {
        foreach ($this->tests as $test) {
            if ($test['status'] === 'PASS') {
                echo "[PASS] {$test['description']}\n";
            } else {
                echo "[FAIL] {$test['description']} - {$test['error']}\n";
            }
        }
    }
}
