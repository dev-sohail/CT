<?php

namespace App\Domains\DashboardAndWidgetFramework\Widgets;

use App\Domains\CalendarAndSchedulingKernel\Models\CalendarEvent;
use App\Domains\ContactsAndRelationshipGraph\Models\Person;
use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\DashboardAndWidgetFramework\Contracts\WidgetData;
use App\Domains\DashboardAndWidgetFramework\Contracts\WidgetProvider;

class OverviewStatsWidget implements WidgetProvider
{
    public function key(): string
    {
        return 'overview-stats';
    }

    public function title(): string
    {
        return 'Life Overview';
    }

    public function description(): string
    {
        return 'Counts of key records across your platform.';
    }

    public function category(): string
    {
        return 'system';
    }

    public function defaultSizeX(): int
    {
        return 4;
    }

    public function defaultSizeY(): int
    {
        return 1;
    }

    public function refreshInterval(): int
    {
        return 1800;
    }

    public function provide(User $user): WidgetData
    {
        $data = [
            'contacts' => Person::forUser($user->id)->count(),
            'events' => CalendarEvent::forUser($user->id)->count(),
        ];

        return new WidgetData(
            key: $this->key(),
            title: $this->title(),
            data: $data,
            refresh_interval: $this->refreshInterval(),
        );
    }
}