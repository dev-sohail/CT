<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\UnifiedPlannerEngine\Models\PlannerItem;
use Laravel\Sanctum\Sanctum;

class PlannerTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_planner_item_crud(): void
    {
        Sanctum::actingAs($this->owner);

        $created = $this->postJson('/api/v1/planner/items', [
            'title' => 'Quarterly review',
            'scope' => 'quarter',
            'due_at' => '2026-10-01T09:00:00Z',
            'priority' => 'high',
        ]);
        $created->assertCreated();
        $id = $created->json('data.id');
        $this->assertEquals('quarter', $created->json('data.scope'));

        $shown = $this->getJson("/api/v1/planner/items/{$id}");
        $shown->assertOk();
        $this->assertEquals('Quarterly review', $shown->json('data.title'));

        $updated = $this->putJson("/api/v1/planner/items/{$id}", ['priority' => 'urgent']);
        $updated->assertOk();
        $this->assertEquals('urgent', $updated->json('data.priority'));

        $this->deleteJson("/api/v1/planner/items/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('planner_items', ['id' => $id]);
    }

    public function test_hierarchy_tree(): void
    {
        $annual = PlannerItem::create(['user_id' => $this->owner->id, 'title' => 'Annual', 'scope' => 'year']);
        $quarter = PlannerItem::create(['user_id' => $this->owner->id, 'parent_id' => $annual->id, 'title' => 'Q3', 'scope' => 'quarter']);
        PlannerItem::create(['user_id' => $this->owner->id, 'parent_id' => $quarter->id, 'title' => 'Task', 'scope' => 'day']);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/planner/tree');

        $res->assertOk();
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals('Annual', $res->json('data.0.title'));
        $this->assertCount(1, $res->json('data.0.children'));
        $this->assertEquals('Q3', $res->json('data.0.children.0.title'));
    }

    public function test_agenda_returns_incomplete_items_in_range(): void
    {
        PlannerItem::create(['user_id' => $this->owner->id, 'title' => 'Today task', 'due_at' => now()->toIso8601String()]);
        PlannerItem::create(['user_id' => $this->owner->id, 'title' => 'Done task', 'status' => 'completed', 'completed_at' => now(), 'due_at' => now()->toIso8601String()]);
        PlannerItem::create(['user_id' => $this->owner->id, 'title' => 'Next week', 'due_at' => now()->addDays(9)->toIso8601String()]);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/planner/agenda');

        $res->assertOk();
        $titles = collect($res->json('data'))->pluck('title')->all();
        $this->assertContains('Today task', $titles);
        $this->assertNotContains('Done task', $titles);
        $this->assertNotContains('Next week', $titles);
    }

    public function test_toggle_completion(): void
    {
        $item = PlannerItem::create(['user_id' => $this->owner->id, 'title' => 'Task']);

        Sanctum::actingAs($this->owner);
        $res = $this->postJson("/api/v1/planner/items/{$item->id}/toggle");

        $res->assertOk();
        $this->assertEquals('completed', $res->json('data.status'));
        $this->assertNotNull($res->json('data.completed_at'));

        $res2 = $this->postJson("/api/v1/planner/items/{$item->id}/toggle");
        $this->assertEquals('pending', $res2->json('data.status'));
    }

    public function test_filter_by_scope_and_incomplete(): void
    {
        PlannerItem::create(['user_id' => $this->owner->id, 'title' => 'Week item', 'scope' => 'week']);
        PlannerItem::create(['user_id' => $this->owner->id, 'title' => 'Day item', 'scope' => 'day']);
        PlannerItem::create(['user_id' => $this->owner->id, 'title' => 'Done day', 'scope' => 'day', 'status' => 'completed', 'completed_at' => now()]);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/planner/items?scope=day&incomplete=1');

        $res->assertOk();
        $titles = collect($res->json('data'))->pluck('title')->all();
        $this->assertEquals(['Day item'], $titles);
    }

    public function test_guest_cannot_access_planner(): void
    {
        $this->getJson('/api/v1/planner/items')->assertUnauthorized();
    }
}