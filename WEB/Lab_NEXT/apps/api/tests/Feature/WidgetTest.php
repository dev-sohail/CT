<?php

namespace Tests\Feature;

use App\Domains\CalendarAndSchedulingKernel\Models\CalendarEvent;
use App\Domains\ContactsAndRelationshipGraph\Models\Person;
use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use Laravel\Sanctum\Sanctum;

class WidgetTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_widget_registry_lists_builtin_widgets(): void
    {
        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/widgets');

        $res->assertOk();
        $keys = collect($res->json('data'))->pluck('key')->all();
        $this->assertContains('upcoming-events', $keys);
        $this->assertContains('birthdays', $keys);
        $this->assertContains('recent-activity', $keys);
        $this->assertContains('overview-stats', $keys);
    }

    public function test_widget_data_returns_payload(): void
    {
        Person::create(['user_id' => $this->owner->id, 'name' => 'Alice']);
        CalendarEvent::create([
            'user_id' => $this->owner->id,
            'title' => 'Standup',
            'starts_at' => now()->addDay()->toIso8601String(),
        ]);

        Sanctum::actingAs($this->owner);

        $events = $this->getJson('/api/v1/widgets/upcoming-events/data');
        $events->assertOk();
        $this->assertCount(1, $events->json('data.data'));
        $this->assertEquals('Standup', $events->json('data.data.0.title'));

        $stats = $this->getJson('/api/v1/widgets/overview-stats/data');
        $stats->assertOk();
        $this->assertEquals(1, $stats->json('data.data.contacts'));
        $this->assertEquals(1, $stats->json('data.data.events'));
    }

    public function test_widget_data_unknown_key_returns_404(): void
    {
        Sanctum::actingAs($this->owner);
        $this->getJson('/api/v1/widgets/does-not-exist/data')->assertNotFound();
    }

    public function test_layout_creates_defaults_for_new_user(): void
    {
        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/dashboard/layout');

        $res->assertOk();
        $this->assertCount(4, $res->json('data'));
        $this->assertContains('upcoming-events', collect($res->json('data'))->pluck('widget_key')->all());
    }

    public function test_layout_is_stable_across_calls(): void
    {
        Sanctum::actingAs($this->owner);
        $this->getJson('/api/v1/dashboard/layout');

        $res = $this->getJson('/api/v1/dashboard/layout');
        $this->assertCount(4, $res->json('data'));
    }

    public function test_save_layout_updates_positions(): void
    {
        Sanctum::actingAs($this->owner);
        $this->getJson('/api/v1/dashboard/layout');

        $res = $this->putJson('/api/v1/dashboard/layout', [
            'items' => [
                ['widget_key' => 'upcoming-events', 'position_x' => 2, 'position_y' => 3, 'width' => 4, 'height' => 1],
            ],
        ]);

        $res->assertOk();
        $this->assertDatabaseHas('user_widget_layout', [
            'user_id' => $this->owner->id,
            'widget_key' => 'upcoming-events',
            'position_x' => 2,
            'position_y' => 3,
            'width' => 4,
            'height' => 1,
        ]);
    }

    public function test_add_widget_to_layout(): void
    {
        Sanctum::actingAs($this->owner);
        $res = $this->postJson('/api/v1/dashboard/layout/birthdays');

        $res->assertCreated();
        $this->assertEquals('birthdays', $res->json('data.widget_key'));
        $this->assertDatabaseHas('user_widget_layout', [
            'user_id' => $this->owner->id,
            'widget_key' => 'birthdays',
        ]);
    }

    public function test_add_unknown_widget_returns_404(): void
    {
        Sanctum::actingAs($this->owner);
        $this->postJson('/api/v1/dashboard/layout/nope')->assertNotFound();
    }

    public function test_remove_widget_from_layout(): void
    {
        Sanctum::actingAs($this->owner);
        $this->getJson('/api/v1/dashboard/layout');

        $this->deleteJson('/api/v1/dashboard/layout/overview-stats')->assertNoContent();
        $this->assertDatabaseMissing('user_widget_layout', [
            'user_id' => $this->owner->id,
            'widget_key' => 'overview-stats',
        ]);
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->getJson('/api/v1/widgets')->assertUnauthorized();
        $this->getJson('/api/v1/dashboard/layout')->assertUnauthorized();
    }
}