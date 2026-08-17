<?php

namespace App\Domains\DashboardAndWidgetFramework\Widgets;

use App\Domains\ContactsAndRelationshipGraph\Models\Person;
use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\DashboardAndWidgetFramework\Contracts\WidgetData;
use App\Domains\DashboardAndWidgetFramework\Contracts\WidgetProvider;

class BirthdaysWidget implements WidgetProvider
{
    public function key(): string
    {
        return 'birthdays';
    }

    public function title(): string
    {
        return 'Upcoming Birthdays';
    }

    public function description(): string
    {
        return 'Contacts with birthdays in the next 30 days.';
    }

    public function category(): string
    {
        return 'contacts';
    }

    public function defaultSizeX(): int
    {
        return 2;
    }

    public function defaultSizeY(): int
    {
        return 2;
    }

    public function refreshInterval(): int
    {
        return 3600;
    }

    public function provide(User $user): WidgetData
    {
        $people = Person::forUser($user->id)
            ->birthdaySoon(30)
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map(fn (Person $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'birthday' => $p->birthday?->toDateString(),
            ]);

        return new WidgetData(
            key: $this->key(),
            title: $this->title(),
            data: $people->values()->all(),
            subtitle: count($people) . ' in the next 30 days',
            refresh_interval: $this->refreshInterval(),
        );
    }
}