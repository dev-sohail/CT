<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\GoalHierarchyAndOKRTracker\Models\Goal;
use App\Domains\GoalHierarchyAndOKRTracker\Models\KeyResult;
use Laravel\Sanctum\Sanctum;

class GoalTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_goal_crud(): void
    {
        Sanctum::actingAs($this->owner);

        $created = $this->postJson('/api/v1/goals', [
            'title' => 'Run a marathon',
            'level' => 'goal',
            'target_at' => '2027-06-01T00:00:00Z',
        ]);
        $created->assertCreated();
        $id = $created->json('data.id');
        $this->assertEquals('goal', $created->json('data.level'));

        $updated = $this->putJson("/api/v1/goals/{$id}", ['title' => 'Finish a marathon']);
        $updated->assertOk();
        $this->assertEquals('Finish a marathon', $updated->json('data.title'));

        $this->deleteJson("/api/v1/goals/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('goals', ['id' => $id]);
    }

    public function test_goal_tree_with_hierarchy(): void
    {
        $life = Goal::create(['user_id' => $this->owner->id, 'title' => 'Health', 'level' => 'life']);
        $goal = Goal::create(['user_id' => $this->owner->id, 'parent_id' => $life->id, 'title' => 'Run', 'level' => 'goal']);
        Goal::create(['user_id' => $this->owner->id, 'parent_id' => $goal->id, 'title' => 'Q1 training', 'level' => 'quarter']);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/goals/tree');

        $res->assertOk();
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals('Health', $res->json('data.0.title'));
        $this->assertCount(1, $res->json('data.0.children'));
        $this->assertEquals('Run', $res->json('data.0.children.0.title'));
    }

    public function test_key_result_progress_and_rollup(): void
    {
        $goal = Goal::create(['user_id' => $this->owner->id, 'title' => 'Read books', 'level' => 'goal']);
        KeyResult::create(['goal_id' => $goal->id, 'title' => 'Books read', 'current_value' => 5, 'target_value' => 10, 'unit' => 'books']);
        $goal->rollupProgress();

        $this->assertEquals(50, $goal->fresh()->progress);

        $child = Goal::create(['user_id' => $this->owner->id, 'parent_id' => $goal->id, 'title' => 'Child', 'level' => 'quarter']);
        $child->update(['progress' => 80]);
        $goal->rollupProgress();

        $this->assertEquals(65, $goal->fresh()->progress); // avg(50, 80)
    }

    public function test_add_and_update_key_result_via_api(): void
    {
        $goal = Goal::create(['user_id' => $this->owner->id, 'title' => 'Save money']);

        Sanctum::actingAs($this->owner);

        $added = $this->postJson("/api/v1/goals/{$goal->id}/key-results", [
            'title' => 'Save $1000',
            'current_value' => 250,
            'target_value' => 1000,
            'unit' => 'USD',
        ]);
        $added->assertCreated();
        $krId = $added->json('data.id');
        $this->assertEquals(25, $added->json('data.progress'));

        $updated = $this->putJson("/api/v1/goals/{$goal->id}/key-results/{$krId}", ['current_value' => 500]);
        $updated->assertOk();
        $this->assertEquals(50, $updated->json('data.progress'));

        $list = $this->getJson("/api/v1/goals/{$goal->id}/key-results");
        $list->assertOk();
        $this->assertCount(1, $list->json('data'));

        $this->deleteJson("/api/v1/goals/{$goal->id}/key-results/{$krId}")->assertNoContent();
        $this->assertDatabaseMissing('key_results', ['id' => $krId]);
    }

    public function test_key_result_update_rolls_up_to_parent(): void
    {
        $parent = Goal::create(['user_id' => $this->owner->id, 'title' => 'Parent']);
        $child = Goal::create(['user_id' => $this->owner->id, 'parent_id' => $parent->id, 'title' => 'Child']);

        Sanctum::actingAs($this->owner);
        $this->postJson("/api/v1/goals/{$child->id}/key-results", [
            'title' => 'KR',
            'current_value' => 30,
            'target_value' => 100,
        ])->assertCreated();

        $this->assertEquals(30, $parent->fresh()->progress);
        $this->assertEquals(30, $child->fresh()->progress);
    }

    public function test_guest_cannot_access_goals(): void
    {
        $this->getJson('/api/v1/goals')->assertUnauthorized();
    }
}