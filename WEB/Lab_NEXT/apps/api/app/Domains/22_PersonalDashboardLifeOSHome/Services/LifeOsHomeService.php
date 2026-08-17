<?php

namespace App\Domains\PersonalDashboardLifeOSHome\Services;

use App\Domains\CalendarAndSchedulingKernel\Models\CalendarEvent;
use App\Domains\ContactsAndRelationshipGraph\Models\Person;
use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\GoalHierarchyAndOKRTracker\Models\Goal;
use App\Domains\UnifiedPlannerEngine\Models\PlannerItem;

class LifeOsHomeService
{
    /**
     * Build the aggregate "Life OS Home" summary for a user.
     */
    public function summary(User $user): array
    {
        $now = now();

        return [
            'today' => [
                'date' => $now->toDateString(),
                'planner_items_due' => PlannerItem::forUser($user->id)
                    ->incomplete()
                    ->whereDate('due_at', $now->toDateString())
                    ->count(),
                'events_today' => CalendarEvent::forUser($user->id)
                    ->whereDate('starts_at', $now->toDateString())
                    ->count(),
            ],
            'planner' => [
                'incomplete' => PlannerItem::forUser($user->id)->incomplete()->count(),
            ],
            'upcoming_events' => CalendarEvent::forUser($user->id)
                ->where('starts_at', '>=', $now)
                ->orderBy('starts_at')
                ->limit(5)
                ->get()
                ->map(fn (CalendarEvent $e) => [
                    'id' => $e->id,
                    'title' => $e->title,
                    'starts_at' => $e->starts_at?->toIso8601String(),
                    'location' => $e->location,
                ])
                ->values()
                ->all(),
            'birthdays' => Person::forUser($user->id)
                ->birthdaySoon(14)
                ->orderBy('name')
                ->limit(5)
                ->get()
                ->map(fn (Person $p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'birthday' => $p->birthday?->toDateString(),
                ])
                ->values()
                ->all(),
            'goals' => [
                'active' => Goal::forUser($user->id)->active()->count(),
            ],
        ];
    }
}