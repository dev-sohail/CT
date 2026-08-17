<?php

namespace Tests\Feature;

use App\Domains\CalendarAndSchedulingKernel\Models\CalendarEvent;
use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use Laravel\Sanctum\Sanctum;

class CalendarTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    private function act(): void
    {
        Sanctum::actingAs($this->owner);
    }

    private function makeEvent(array $overrides = []): CalendarEvent
    {
        return CalendarEvent::create(array_merge([
            'user_id' => $this->owner->id,
            'title' => 'Test Event',
            'starts_at' => '2026-09-01T10:00:00Z',
            'is_all_day' => false,
        ], $overrides));
    }

    // ------------------------------------------------------------------
    // CRUD
    // ------------------------------------------------------------------

    public function test_guest_cannot_access_calendar(): void
    {
        $this->getJson('/api/v1/calendar/events')->assertUnauthorized();
    }

    public function test_create_calendar_event(): void
    {
        $this->act();

        $response = $this->postJson('/api/v1/calendar/events', [
            'title' => 'Team Standup',
            'description' => 'Weekly sync',
            'location' => 'Room A',
            'starts_at' => '2026-09-01T09:00:00Z',
            'ends_at' => '2026-09-01T09:30:00Z',
            'is_all_day' => false,
            'timezone' => 'UTC',
            'status' => 'confirmed',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Team Standup')
            ->assertJsonStructure(['data' => ['id', 'title', 'starts_at', 'status']]);

        $this->assertDatabaseHas('calendar_events', ['title' => 'Team Standup']);
    }

    public function test_validates_required_fields(): void
    {
        $this->act();

        $this->postJson('/api/v1/calendar/events', [
            'description' => 'Missing title',
        ])->assertStatus(422);
    }

    public function test_list_user_events(): void
    {
        $this->act();
        $this->makeEvent(['title' => 'Event A', 'starts_at' => '2026-09-01T10:00:00Z']);
        $this->makeEvent(['title' => 'Event B', 'starts_at' => '2026-09-02T10:00:00Z']);

        $response = $this->getJson('/api/v1/calendar/events');

        $response->assertOk()
            ->assertJsonStructure(['data' => [['id', 'title', 'starts_at']]]);

        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_update_event(): void
    {
        $this->act();
        $event = $this->makeEvent(['title' => 'Original']);

        $this->putJson("/api/v1/calendar/events/{$event->id}", [
            'title' => 'Updated Title',
            'status' => 'tentative',
        ])->assertOk()
            ->assertJsonPath('data.title', 'Updated Title')
            ->assertJsonPath('data.status', 'tentative');
    }

    public function test_delete_event(): void
    {
        $this->act();
        $event = $this->makeEvent(['title' => 'To Delete']);

        $this->deleteJson("/api/v1/calendar/events/{$event->id}")
            ->assertStatus(204);

        $this->assertSoftDeleted('calendar_events', ['id' => $event->id]);
    }

    // ------------------------------------------------------------------
    // Ownership isolation
    // ------------------------------------------------------------------

    public function test_other_user_cannot_access_events(): void
    {
        $event = $this->makeEvent(['title' => 'Owner Event']);
        $bob = User::factory()->create();
        Sanctum::actingAs($bob);

        $this->getJson("/api/v1/calendar/events/{$event->id}")->assertStatus(404);
        $this->putJson("/api/v1/calendar/events/{$event->id}", ['title' => 'Hacked'])->assertStatus(404);
        $this->deleteJson("/api/v1/calendar/events/{$event->id}")->assertStatus(404);

        // Owner still sees it
        $this->act();
        $this->getJson("/api/v1/calendar/events/{$event->id}")
            ->assertOk()
            ->assertJsonPath('data.title', 'Owner Event');
    }

    // ------------------------------------------------------------------
    // Recurring events
    // ------------------------------------------------------------------

    public function test_daily_recurrence_expand(): void
    {
        $this->act();
        $this->makeEvent([
            'title' => 'Daily Standup',
            'starts_at' => '2026-09-01T09:00:00Z',
            'ends_at' => '2026-09-01T09:30:00Z',
            'recurrence_rule' => 'FREQ=DAILY;INTERVAL=1;COUNT=5',
        ]);

        $response = $this->getJson('/api/v1/calendar/events/expand?from=2026-09-01T00:00:00Z&to=2026-09-10T23:59:59Z');

        $response->assertOk();
        $occurrences = $response->json('data');
        $this->assertGreaterThanOrEqual(5, count($occurrences));
        $this->assertEquals('Daily Standup', $occurrences[0]['title']);
    }

    public function test_weekly_recurrence_with_byday(): void
    {
        $this->act();
        $this->makeEvent([
            'title' => 'Weekly Meeting',
            'starts_at' => '2026-09-01T14:00:00Z',
            'ends_at' => '2026-09-01T15:00:00Z',
            'recurrence_rule' => 'FREQ=WEEKLY;BYDAY=MO,WE,FR;COUNT=6',
        ]);

        $response = $this->getJson('/api/v1/calendar/events/expand?from=2026-09-01T00:00:00Z&to=2026-09-30T23:59:59Z');

        $response->assertOk();
        $occurrences = $response->json('data');
        $this->assertGreaterThanOrEqual(6, count($occurrences));
    }

    public function test_non_recurring_events_appear_in_expand(): void
    {
        $this->act();
        $this->makeEvent([
            'title' => 'One-time Event',
            'starts_at' => '2026-09-05T10:00:00Z',
        ]);

        $response = $this->getJson('/api/v1/calendar/events/expand?from=2026-09-01T00:00:00Z&to=2026-09-10T23:59:59Z');

        $response->assertOk();
        $titles = array_column($response->json('data'), 'title');
        $this->assertContains('One-time Event', $titles);
    }
}