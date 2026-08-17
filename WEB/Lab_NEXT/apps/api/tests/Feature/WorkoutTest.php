<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\WorkoutAndTrainingPlanner\Models\Workout;
use Laravel\Sanctum\Sanctum;

class WorkoutTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_workout_crud_and_completion(): void
    {
        Sanctum::actingAs($this->owner);
        $created = $this->postJson('/api/v1/workouts', ['name' => 'Upper body', 'type' => 'strength', 'duration_minutes' => 45, 'exercises' => [['name' => 'Push-up', 'sets' => 3]]]);
        $created->assertCreated();
        $id = $created->json('data.id');
        $done = $this->postJson("/api/v1/workouts/{$id}/complete");
        $done->assertOk();
        $this->assertEquals('completed', $done->json('data.status'));
        $this->deleteJson("/api/v1/workouts/{$id}")->assertNoContent();
    }

    public function test_stats_and_filters(): void
    {
        Workout::create(['user_id' => $this->owner->id, 'name' => 'Run', 'type' => 'cardio', 'status' => 'completed', 'duration_minutes' => 30, 'calories_burned' => 250]);
        Workout::create(['user_id' => $this->owner->id, 'name' => 'Yoga', 'type' => 'mobility', 'status' => 'planned']);
        Sanctum::actingAs($this->owner);
        $this->assertCount(1, $this->getJson('/api/v1/workouts?type=cardio')->json('data'));
        $stats = $this->getJson('/api/v1/workouts/stats');
        $stats->assertOk();
        $this->assertEquals(2, $stats->json('data.total'));
        $this->assertEquals(30, $stats->json('data.total_minutes'));
        $this->assertEquals(250, $stats->json('data.total_calories'));
    }

    public function test_guest_cannot_access_workouts(): void
    {
        $this->getJson('/api/v1/workouts')->assertUnauthorized();
    }
}
