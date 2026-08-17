<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\TimeAuditAndTimeBlockAnalyzer\Models\TimeEntry;
use Laravel\Sanctum\Sanctum;

class TimeAuditTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_time_entry_crud(): void
    {
        Sanctum::actingAs($this->owner);

        $created = $this->postJson('/api/v1/time/entries', [
            'started_at' => '2026-09-01T09:00:00Z',
            'ended_at' => '2026-09-01T10:30:00Z',
            'activity' => 'Deep work',
            'category' => 'work',
        ]);
        $created->assertCreated();
        $id = $created->json('data.id');
        $this->assertEquals(90, $created->json('data.duration_minutes'));

        $updated = $this->putJson("/api/v1/time/entries/{$id}", ['category' => 'focus']);
        $updated->assertOk();
        $this->assertEquals('focus', $updated->json('data.category'));

        $this->deleteJson("/api/v1/time/entries/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('time_entries', ['id' => $id]);
    }

    public function test_analytics_aggregates_by_category_and_day(): void
    {
        TimeEntry::create([
            'user_id' => $this->owner->id,
            'started_at' => now()->setTime(9, 0),
            'ended_at' => now()->setTime(11, 0),
            'activity' => 'Coding',
            'category' => 'work',
        ]);
        TimeEntry::create([
            'user_id' => $this->owner->id,
            'started_at' => now()->setTime(14, 0),
            'ended_at' => now()->setTime(16, 0),
            'activity' => 'Gym',
            'category' => 'health',
        ]);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/time/analytics');

        $res->assertOk();
        $this->assertEquals(240, $res->json('data.total_minutes'));
        $this->assertEquals(120, $res->json('data.by_category.work'));
        $this->assertEquals(120, $res->json('data.by_category.health'));
        $this->assertEquals('work', $res->json('data.top_category'));
    }

    public function test_analytics_respects_date_range(): void
    {
        TimeEntry::create([
            'user_id' => $this->owner->id,
            'started_at' => now()->subDays(10)->setTime(10, 0),
            'ended_at' => now()->subDays(10)->setTime(12, 0),
            'activity' => 'Old',
            'category' => 'work',
        ]);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/time/analytics?from=' . now()->startOfWeek()->toDateString() . '&to=' . now()->endOfWeek()->toDateString());

        $res->assertOk();
        $this->assertEquals(0, $res->json('data.total_minutes'));
    }

    public function test_list_filters_by_category(): void
    {
        TimeEntry::create(['user_id' => $this->owner->id, 'started_at' => now()->setTime(9, 0), 'ended_at' => now()->setTime(10, 0), 'activity' => 'A', 'category' => 'work']);
        TimeEntry::create(['user_id' => $this->owner->id, 'started_at' => now()->setTime(11, 0), 'ended_at' => now()->setTime(12, 0), 'activity' => 'B', 'category' => 'personal']);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/time/entries?category=work');

        $res->assertOk();
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals('A', $res->json('data.0.activity'));
    }

    public function test_guest_cannot_access_time_entries(): void
    {
        $this->getJson('/api/v1/time/entries')->assertUnauthorized();
    }
}