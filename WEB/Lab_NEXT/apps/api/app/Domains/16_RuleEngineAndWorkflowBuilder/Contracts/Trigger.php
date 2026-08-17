<?php

namespace App\Domains\RuleEngineAndWorkflowBuilder\Contracts;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;

/**
 * A trigger produces the evaluation context for rules of a given type.
 * E.g. the "calendar" trigger yields upcoming events, the "contacts"
 * trigger yields birthday/relationship context.
 */
interface Trigger
{
    public function key(): string;

    public function label(): string;

    public function buildContext(User $user, array $config = []): array;
}