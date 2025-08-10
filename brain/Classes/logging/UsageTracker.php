<?php
/**
 * Class UsageTracker
 *
 * Tracks usage statistics for features, API calls, or other measurable actions.
 */
class UsageTracker
{
    protected array $usageData = [];

    /**
     * Record an action with an optional count.
     */
    public function record(string $action, int $count = 1): void
    {
        if (!isset($this->usageData[$action])) {
            $this->usageData[$action] = 0;
        }
        $this->usageData[$action] += $count;
    }

    /**
     * Get the usage count for a specific action.
     */
    public function getUsage(string $action): int
    {
        return $this->usageData[$action] ?? 0;
    }

    /**
     * Get all usage data.
     */
    public function getAllUsage(): array
    {
        return $this->usageData;
    }

    /**
     * Reset usage data for a specific action or all actions.
     */
    public function reset(?string $action = null): void
    {
        if ($action === null) {
            $this->usageData = [];
        } else {
            unset($this->usageData[$action]);
        }
    }
}
