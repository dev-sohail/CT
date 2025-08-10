<?php
/**
 * Class WorkflowEngine
 *
 * A simple workflow engine for defining and executing steps in a sequence.
 */
class WorkflowEngine
{
    protected array $steps = [];
    protected array $context = [];

    /**
     * Add a step to the workflow.
     */
    public function addStep(string $name, callable $callback): void
    {
        $this->steps[$name] = $callback;
    }

    /**
     * Run the workflow from the beginning or a specified step.
     */
    public function run(?string $startStep = null): void
    {
        $start = $startStep ? array_search($startStep, array_keys($this->steps), true) : 0;
        if ($start === false) {
            throw new \InvalidArgumentException("Start step '$startStep' not found in workflow.");
        }

        $steps = array_slice($this->steps, $start, null, true);
        foreach ($steps as $name => $callback) {
            $result = call_user_func($callback, $this->context);
            if ($result === false) {
                break; // Stop workflow if a step returns false
            }
        }
    }

    /**
     * Set workflow context data.
     */
    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    /**
     * Get workflow context data.
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
