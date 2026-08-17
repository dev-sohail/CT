<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\LifeTimelineAndHistoryArchive\Models\TimelineEvent;
use Laravel\Sanctum\Sanctum;

class TimelineEventTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_timeline_event_crud(): void
    {
        Sanctum::actingAs($this->owner);
        $created = $this->postJson('/api/v1/timeline', ['event_date' => '2020-01-01', 'title' => 'Graduation', 'type' => 'milestone', 'significance' => 5]);
        $created->assertCreated();
        $id = $created->json('data.id');
        $this->assertEquals('milestone', $created->json('data.type'));
        $this->putJson("/api/v1/timeline/{$id}", ['title' => 'University graduation'])->assertOk();
        $this->deleteJson("/api/v1/timeline/{$id}")->assertNoContent();
    }

    public function test_range_milestones_and_stats(): void
    {
        TimelineEvent::create(['user_id' => $this->owner->id, 'event_date' => '2020-01-01', 'title' => 'M', 'type' => 'milestone', 'category' => 'career', 'significance' => 5]);
        TimelineEvent::create(['user_id' => $this->owner->id, 'event_date' => '2021-01-01', 'title' => 'E', 'type' => 'event', 'category' => 'travel', 'significance' => 3]);
        Sanctum::actingAs($this->owner);
        $range = $this->getJson('/api/v1/timeline?from=2019-01-01&to=2020-12-31');
        $range->assertOk();
        $this->assertCount(1, $range->json('data'));
        $this->assertCount(1, $this->getJson('/api/v1/timeline/milestones')->json('data'));
        $stats = $this->getJson('/api/v1/timeline/stats');
        $stats->assertOk();
        $this->assertEquals(2, $stats->json('data.total'));
        $this->assertEquals(4, $stats->json('data.average_significance'));
    }

    public function test_guest_cannot_access_timeline(): void
    {
        $this->getJson('/api/v1/timeline')->assertUnauthorized();
    }
}
