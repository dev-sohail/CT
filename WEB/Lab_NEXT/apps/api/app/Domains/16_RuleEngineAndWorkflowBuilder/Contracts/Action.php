<?php

namespace App\Domains\RuleEngineAndWorkflowBuilder\Contracts;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;

interface Action
{
    public function key(): string;

    public function label(): string;

    public function execute(User $user, array $config, array $context): array;
}