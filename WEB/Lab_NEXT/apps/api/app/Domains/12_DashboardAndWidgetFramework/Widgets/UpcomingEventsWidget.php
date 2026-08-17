<?php

namespace App\Domains\DashboardAndWidgetFramework\Widgets;

use App\Domains\CalendarAndSchedulingKernel\Models\CalendarEvent;
use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\DashboardAndWidgetFramework\Contracts\WidgetData;
use App\Domains\DashboardAndWidgetFramework\Contracts\WidgetProvider;

class UpcomingEventsWidget implements WidgetProvider
{
    public function key(): string
    {
        return 'upcoming-events';
    }

    public function title(): string
    {
        return 'Upcoming Events';
    }

    public function description(): string
    {
        return 'Your next scheduled calendar events.';
    }

    public function category(): string
    {
        return 'calendar';
    }

    public function defaultSizeX(): int
    {
        return 3;
    }

    public function defaultSizeY(): int
    {
        return 2;
    }

    public function refreshInterval(): int
    {
        return 300;
    }

    public function provide(User $user): WidgetData
    {
        $events = CalendarEvent::forUser($user->id)
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->limit(5)
            ->get()
            ->map(fn (CalendarEvent $e) => [
                'id' => $e->id,
                'title' => $e->title,
                'starts_at' => $e->starts_at?->toIso8601String(),
                'location' => $e->location,
                'status' => $e->status,
            ]);

        return new WidgetData(
            key: $this->key(),
            title: $this->title(),
            data: $events->values()->all(),
            subtitle: count($events) . ' upcoming',
            refresh_interval: $this->refreshInterval(),
        );
    }
}