<?php
/**
 * Class ScenarioRunner
 *
 * A utility for defining and executing sequential scenarios or workflows.
 */
class ScenarioRunner
{
    protected array $steps = [];

    /**
     * Add a step to the scenario.
     */
    public function addStep(callable $step): self
    {
        $this->steps[] = $step;
        return $this;
    }

    /**
     * Run all steps in sequence.
     */
    public function run(): void
    {
        foreach ($this->steps as $index => $step) {
            try {
                $step($index);
            } catch (\Throwable $e) {
                echo "Step {$index} failed: " . $e->getMessage() . PHP_EOL;
                break;
            }
        }
    }

    /**
     * Clear all steps.
     */
    public function clear(): void
    {
        $this->steps = [];
    }
}
