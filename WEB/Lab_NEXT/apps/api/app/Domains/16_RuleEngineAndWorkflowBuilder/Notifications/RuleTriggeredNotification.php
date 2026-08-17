<?php

namespace App\Domains\RuleEngineAndWorkflowBuilder\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RuleTriggeredNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $body,
        public ?int $ruleId = null,
    ) {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'rule_id' => $this->ruleId,
            'category' => 'rule-engine',
        ];
    }
}