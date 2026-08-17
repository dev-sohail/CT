<?php

namespace Tests\Feature;

use App\Domains\CalendarAndSchedulingKernel\Models\CalendarEvent;
use App\Domains\ContactsAndRelationshipGraph\Models\Person;
use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\UnifiedPlannerEngine\Models\PlannerItem;
use Laravel\Sanctum\Sanctum;

class LifeOsHomeTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_summary_aggregates_life_os_state(): void
    {
        PlannerItem::create(['user_id' => $this->owner->id, 'title' => 'Task', 'due_at' => now()->toIso8601String()]);
        PlannerItem::create(['user_id' => $this->owner->id, 'title' => 'Later', 'due_at' => now()->addDays(3)->toIso8601String()]);
        CalendarEvent::create(['user_id' => $this->owner->id, 'title' => 'Standup', 'starts_at' => now()->addMinutes(5)->toIso8601String()]);
        Person::create(['user_id' => $this->owner->id, 'name' => 'Mom', 'birthday' => now()->addDays(5)->toDateString()]);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/home/summary');

        $res->assertOk();
        $this->assertEquals(1, $res->json('data.today.planner_items_due'));
        $this->assertEquals(1, $res->json('data.today.events_today'));
        $this->assertEquals(2, $res->json('data.planner.incomplete'));
        $this->assertCount(1, $res->json('data.upcoming_events'));
        $this->assertEquals('Standup', $res->json('data.upcoming_events.0.title'));
        $this->assertCount(1, $res->json('data.birthdays'));
        $this->assertEquals('Mom', $res->json('data.birthdays.0.name'));
    }

    public function test_summary_guest_unauthorized(): void
    {
        $this->getJson('/api/v1/home/summary')->assertUnauthorized();
    }
}