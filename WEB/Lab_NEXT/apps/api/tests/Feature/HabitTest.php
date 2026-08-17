<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\HabitTrackingEngine\Models\Habit;
use Laravel\Sanctum\Sanctum;

class HabitTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_habit_crud(): void
    {
        Sanctum::actingAs($this->owner);
        $created = $this->postJson('/api/v1/habits', [
            'name' => 'Read', 'frequency' => 'daily', 'target_count' => 1,
        ]);
        $created->assertCreated();
        $id = $created->json('data.id');

        $updated = $this->putJson("/api/v1/habits/{$id}", ['active' => false]);
        $updated->assertOk();
        $this->assertFalse($updated->json('data.active'));

        $this->deleteJson("/api/v1/habits/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('habits', ['id' => $id]);
    }

    public function test_log_is_idempotent_and_today_reports_completion(): void
    {
        $habit = Habit::create(['user_id' => $this->owner->id, 'name' => 'Exercise', 'target_count' => 2]);
        Sanctum::actingAs($this->owner);

        $this->postJson("/api/v1/habits/{$habit->id}/log", ['count' => 1])->assertOk();
        $this->postJson("/api/v1/habits/{$habit->id}/log", ['count' => 2])->assertOk();

        $this->assertDatabaseCount('habit_logs', 1);
        $today = $this->getJson('/api/v1/habits/today');
        $today->assertOk();
        $this->assertTrue($today->json('data.items.0.completed'));
    }

    public function test_streaks_count_consecutive_completed_days(): void
    {
        $habit = Habit::create(['user_id' => $this->owner->id, 'name' => 'Journal']);
        foreach ([now()->subDay(), now()] as $date) {
            $habit->logs()->create(['user_id' => $this->owner->id, 'logged_for' => $date->toDateString(), 'count' => 1, 'completed' => true]);
        }

        Sanctum::actingAs($this->owner);
        $res = $this->postJson("/api/v1/habits/{$habit->id}/log", ['count' => 1]);
        $res->assertOk();
        $this->assertGreaterThanOrEqual(2, $res->json('data.streak.current_streak'));
        $this->assertGreaterThanOrEqual(2, $res->json('data.streak.longest_streak'));
    }

    public function test_stats_and_guest_access(): void
    {
        $this->getJson('/api/v1/habits')->assertUnauthorized();
        Habit::create(['user_id' => $this->owner->id, 'name' => 'One']);
        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/habits/stats');
        $res->assertOk();
        $this->assertEquals(1, $res->json('data.total'));
        $this->assertEquals(1, $res->json('data.active'));
    }
}
