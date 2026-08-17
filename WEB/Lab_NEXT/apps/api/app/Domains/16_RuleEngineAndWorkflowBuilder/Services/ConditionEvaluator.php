<?php

namespace App\Domains\RuleEngineAndWorkflowBuilder\Services;

use App\Domains\RuleEngineAndWorkflowBuilder\Contracts\Condition;
use App\Domains\RuleEngineAndWorkflowBuilder\Models\RuleCondition;

class ConditionEvaluator implements Condition
{
    public function key(): string
    {
        return 'comparison';
    }

    public function label(): string
    {
        return 'Comparison';
    }

    public function evaluate(mixed $actual, string $operator, mixed $expected, ?array $context = null): bool
    {
        if ($operator === 'exists') {
            return $actual !== null && $actual !== '';
        }
        if ($operator === 'not_exists') {
            return $actual === null || $actual === '';
        }

        switch ($operator) {
            case 'eq':
            case 'equals':
                return $actual == $expected;
            case 'neq':
            case 'not_equals':
                return $actual != $expected;
            case 'gt':
                return $actual > $expected;
            case 'gte':
                return $actual >= $expected;
            case 'lt':
                return $actual < $expected;
            case 'lte':
                return $actual <= $expected;
            case 'contains':
                return is_string($actual) && str_contains($actual, (string) $expected);
            case 'not_contains':
                return !is_string($actual) || !str_contains($actual, (string) $expected);
            case 'starts_with':
                return is_string($actual) && str_starts_with($actual, (string) $expected);
            case 'ends_with':
                return is_string($actual) && str_ends_with($actual, (string) $expected);
            case 'in':
                return in_array($actual, (array) $expected, true);
            case 'not_in':
                return !in_array($actual, (array) $expected, true);
            case 'between':
                [$lo, $hi] = array_pad((array) $expected, 2, null);
                return $lo !== null && $hi !== null && $actual >= $lo && $actual <= $hi;
            case 'date_before':
                return strtotime((string) $actual) < strtotime((string) $expected);
            case 'date_after':
                return strtotime((string) $actual) > strtotime((string) $expected);
            case 'is_today':
                return $actual !== null && \Carbon\Carbon::parse($actual)->isToday();
            default:
                return false;
        }
    }

    /**
     * Resolve the actual value for a condition from the context, using
     * dot notation for nested keys, then evaluate it.
     */
    public function matches(RuleCondition $condition, array $context): bool
    {
        $actual = data_get($context, $condition->field);

        if ($condition->type === 'date') {
            return $this->evaluateDate($actual, $condition->operator, $condition->value);
        }

        return $this->evaluate($actual, $condition->operator, $condition->value, $context);
    }

    private function evaluateDate(mixed $actual, string $operator, mixed $expected): bool
    {
        if ($actual === null || $actual === '') {
            return false;
        }
        if ($operator === 'is_today') {
            return \Carbon\Carbon::parse($actual)->isToday();
        }
        if ($operator === 'between') {
            [$lo, $hi] = array_pad((array) $expected, 2, null);
            $ts = strtotime((string) $actual);
            return $ts >= strtotime((string) $lo) && $ts <= strtotime((string) $hi);
        }
        return $this->evaluate($actual, $operator, $expected);
    }
}