<?php

namespace App\Domains\RuleEngineAndWorkflowBuilder\Actions;

use App\Domains\AuditTrailAndActivityTimeline\Models\AuditLog;
use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\RuleEngineAndWorkflowBuilder\Contracts\Action;

class AuditAction implements Action
{
    public function key(): string
    {
        return 'audit';
    }

    public function label(): string
    {
        return 'Write audit entry';
    }

    public function execute(User $user, array $config, array $context): array
    {
        $entry = AuditLog::create([
            'user_id' => $user->id,
            'action' => $config['action'] ?? 'rule.triggered',
            'subject_type' => $config['subject_type'] ?? 'rule',
            'subject_id' => $config['subject_id'] ?? $config['rule_id'] ?? null,
            'meta' => $config['meta'] ?? ['context' => array_slice($context, 0, 5)],
        ]);

        return ['written' => true, 'audit_log_id' => $entry->id];
    }
}