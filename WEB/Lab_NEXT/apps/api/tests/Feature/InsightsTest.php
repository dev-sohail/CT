<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\TimeAuditAndTimeBlockAnalyzer\Models\TimeEntry;
use App\Domains\UnifiedPlannerEngine\Models\PlannerItem;
use Laravel\Sanctum\Sanctum;

class InsightsTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_overview_aggregates_completion(): void
    {
        PlannerItem::create(['user_id' => $this->owner->id, 'title' => 'Done A', 'status' => 'completed', 'completed_at' => now()]);
        PlannerItem::create(['user_id' => $this->owner->id, 'title' => 'Done B', 'status' => 'completed', 'completed_at' => now()->subDays(2)]);
        PlannerItem::create(['user_id' => $this->owner->id, 'title' => 'Pending']);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/insights/overview');

        $res->assertOk();
        $this->assertEquals(3, $res->json('data.planner.total'));
        $this->assertEquals(2, $res->json('data.planner.completed'));
        $this->assertEquals(1, $res->json('data.planner.incomplete'));
        $this->assertEquals(66.7, $res->json('data.planner.completion_rate'));
        $this->assertEquals(1, $res->json('data.this_week.completed_today'));
    }

    public function test_completion_by_scope_and_priority(): void
    {
        PlannerItem::create(['user_id' => $this->owner->id, 'title' => 'A', 'scope' => 'day', 'priority' => 'high', 'status' => 'completed', 'completed_at' => now()]);
        PlannerItem::create(['user_id' => $this->owner->id, 'title' => 'B', 'scope' => 'day', 'priority' => 'high', 'status' => 'pending']);
        PlannerItem::create(['user_id' => $this->owner->id, 'title' => 'C', 'scope' => 'week', 'priority' => 'low', 'status' => 'pending']);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/insights/completion');

        $res->assertOk();
        $this->assertEquals(2, $res->json('data.by_scope.day.total'));
        $this->assertEquals(50, $res->json('data.by_scope.day.rate'));
        $this->assertEquals(2, $res->json('data.by_priority.high.total'));
        $this->assertEquals(50, $res->json('data.by_priority.high.rate'));
    }

    public function test_trends_returns_daily_points(): void
    {
        PlannerItem::create(['user_id' => $this->owner->id, 'title' => 'A', 'status' => 'completed', 'completed_at' => now()]);
        PlannerItem::create(['user_id' => $this->owner->id, 'title' => 'B', 'status' => 'completed', 'completed_at' => now()]);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/insights/trends?days=7');

        $res->assertOk();
        $this->assertCount(7, $res->json('data'));
        $this->assertEquals(2, $res->json('data.6.completed'));
    }

    public function test_focus_time_by_category(): void
    {
        TimeEntry::create([
            'user_id' => $this->owner->id,
            'started_at' => now()->setTime(9, 0),
            'ended_at' => now()->setTime(10, 0),
            'activity' => 'Coding',
            'category' => 'work',
        ]);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/insights/focus');

        $res->assertOk();
        $this->assertEquals(60, $res->json('data.total_minutes'));
        $this->assertEquals('work', $res->json('data.top_category'));
    }

    public function test_guest_cannot_access_insights(): void
    {
        $this->getJson('/api/v1/insights/overview')->assertUnauthorized();
    }
}