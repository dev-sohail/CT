<?php

namespace App\Domains\RuleEngineAndWorkflowBuilder\Triggers;

use App\Domains\CalendarAndSchedulingKernel\Models\CalendarEvent;
use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\RuleEngineAndWorkflowBuilder\Contracts\Trigger;

class CalendarTrigger implements Trigger
{
    public function key(): string
    {
        return 'calendar';
    }

    public function label(): string
    {
        return 'Calendar';
    }

    public function buildContext(User $user, array $config = []): array
    {
        $days = $config['days'] ?? 14;
        $from = now();
        $to = now()->addDays($days);

        $events = CalendarEvent::forUser($user->id)
            ->where('starts_at', '>=', $from)
            ->where('starts_at', '<=', $to)
            ->orderBy('starts_at')
            ->get()
            ->map(fn (CalendarEvent $e) => [
                'title' => $e->title,
                'starts_at' => $e->starts_at?->toIso8601String(),
                'days_until' => $e->starts_at ? (int) $from->diffInDays($e->starts_at, false) : null,
                'location' => $e->location,
                'status' => $e->status,
            ])
            ->values()
            ->all();

        $next = $events[0] ?? null;

        return [
            'events' => $events,
            'event_count' => count($events),
            'next_event_days' => $next ? $next['days_until'] : null,
            'next_event_title' => $next['title'] ?? null,
        ];
    }
}