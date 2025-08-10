<?php
/**
 * Class Installer
 *
 * Handles installation routines for an application.
 */
class Installer
{
    protected array $steps = [];

    /**
     * Add an installation step.
     */
    public function addStep(string $name, callable $callback): self
    {
        $this->steps[] = [
            'name' => $name,
            'callback' => $callback
        ];
        return $this;
    }

    /**
     * Run all installation steps sequentially.
     */
    public function run(): void
    {
        foreach ($this->steps as $step) {
            echo "Running step: {$step['name']}...\n";
            call_user_func($step['callback']);
            echo "Step '{$step['name']}' completed.\n";
        }
        echo "Installation complete.\n";
    }

    /**
     * Clear all registered steps.
     */
    public function clearSteps(): void
    {
        $this->steps = [];
    }
}
