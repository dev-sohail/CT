<?php

namespace App\Domains\RuleEngineAndWorkflowBuilder\Actions;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\RuleEngineAndWorkflowBuilder\Contracts\Action;
use App\Domains\RuleEngineAndWorkflowBuilder\Notifications\RuleTriggeredNotification;

class NotifyAction implements Action
{
    public function key(): string
    {
        return 'notify';
    }

    public function label(): string
    {
        return 'Send notification';
    }

    public function execute(User $user, array $config, array $context): array
    {
        $title = $config['title'] ?? 'Rule triggered';
        $body = $config['body'] ?? 'A rule you configured has fired.';
        $ruleId = $config['rule_id'] ?? null;

        $user->notify(new RuleTriggeredNotification($title, $body, $ruleId));

        return ['sent' => true, 'title' => $title];
    }
}