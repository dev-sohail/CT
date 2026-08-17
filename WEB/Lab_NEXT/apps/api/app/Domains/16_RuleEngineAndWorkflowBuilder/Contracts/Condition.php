<?php

namespace App\Domains\RuleEngineAndWorkflowBuilder\Contracts;

interface Condition
{
    public function key(): string;

    public function label(): string;

    public function evaluate(mixed $actual, string $operator, mixed $expected, ?array $context = null): bool;
}