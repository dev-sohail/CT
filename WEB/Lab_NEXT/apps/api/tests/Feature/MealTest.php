<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\NutritionAndMealPlanner\Models\Meal;
use Laravel\Sanctum\Sanctum;

class MealTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_meal_crud_and_daily_totals(): void
    {
        Sanctum::actingAs($this->owner);
        $created = $this->postJson('/api/v1/meals', ['eaten_on' => now()->toDateString(), 'meal_type' => 'lunch', 'name' => 'Salad', 'calories' => 450, 'protein_grams' => 30, 'carbs_grams' => 40, 'fat_grams' => 15]);
        $created->assertCreated();
        $id = $created->json('data.id');
        $daily = $this->getJson('/api/v1/meals/daily');
        $daily->assertOk();
        $this->assertEquals(450, $daily->json('data.totals.calories'));
        $this->deleteJson("/api/v1/meals/{$id}")->assertNoContent();
    }

    public function test_stats_and_filter(): void
    {
        Meal::create(['user_id' => $this->owner->id, 'eaten_on' => now()->toDateString(), 'meal_type' => 'breakfast', 'name' => 'Oats', 'calories' => 300]);
        Meal::create(['user_id' => $this->owner->id, 'eaten_on' => now()->toDateString(), 'meal_type' => 'dinner', 'name' => 'Rice', 'calories' => 700]);
        Sanctum::actingAs($this->owner);
        $this->assertCount(1, $this->getJson('/api/v1/meals?meal_type=dinner')->json('data'));
        $stats = $this->getJson('/api/v1/meals/stats');
        $stats->assertOk();
        $this->assertEquals(2, $stats->json('data.total_meals'));
        $this->assertEquals(1000, $stats->json('data.total_calories'));
    }

    public function test_guest_cannot_access_meals(): void
    {
        $this->getJson('/api/v1/meals')->assertUnauthorized();
    }
}
