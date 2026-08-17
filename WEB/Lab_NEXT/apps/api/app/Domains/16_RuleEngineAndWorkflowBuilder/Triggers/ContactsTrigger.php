<?php

namespace App\Domains\RuleEngineAndWorkflowBuilder\Triggers;

use App\Domains\ContactsAndRelationshipGraph\Models\Person;
use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\RuleEngineAndWorkflowBuilder\Contracts\Trigger;

class ContactsTrigger implements Trigger
{
    public function key(): string
    {
        return 'contacts';
    }

    public function label(): string
    {
        return 'Contacts';
    }

    public function buildContext(User $user, array $config = []): array
    {
        $days = $config['days'] ?? 30;
        $now = now();

        $people = Person::forUser($user->id)->get()->map(function (Person $p) use ($now) {
            $daysUntilBirthday = null;
            if ($p->birthday) {
                $birthday = $p->birthday->copy()->setYear($now->year);
                if ($birthday->lt($now->startOfDay())) {
                    $birthday->addYear();
                }
                $daysUntilBirthday = (int) $now->startOfDay()->diffInDays($birthday);
            }
            return [
                'name' => $p->name,
                'relationship_type' => $p->relationship_type,
                'birthday' => $p->birthday?->toDateString(),
                'days_until_birthday' => $daysUntilBirthday,
            ];
        })->values();

        $birthdaysSoon = $people->filter(
            fn ($p) => $p['days_until_birthday'] !== null && $p['days_until_birthday'] <= $days
        )->values();

        return [
            'people' => $people->all(),
            'contact_count' => $people->count(),
            'birthdays_soon' => $birthdaysSoon->all(),
            'birthdays_soon_count' => $birthdaysSoon->count(),
        ];
    }
}