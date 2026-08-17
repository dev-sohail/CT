<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use Laravel\Sanctum\Sanctum;

class WellnessLogTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_hydration_movement_and_today_summary(): void
    {
        Sanctum::actingAs($this->owner);
        $this->postJson('/api/v1/wellness/hydration', ['milliliters' => 500, 'beverage' => 'water'])->assertCreated();
        $this->postJson('/api/v1/wellness/hydration', ['milliliters' => 250, 'beverage' => 'tea'])->assertCreated();
        $this->postJson('/api/v1/wellness/movement', ['minutes' => 30, 'activity' => 'walking', 'steps' => 3000])->assertCreated();
        $today = $this->getJson('/api/v1/wellness/today');
        $today->assertOk();
        $this->assertEquals(750, $today->json('data.hydration.milliliters'));
        $this->assertEquals(500, $today->json('data.hydration.water_milliliters'));
        $this->assertEquals(30, $today->json('data.movement.minutes'));
        $this->assertEquals(3000, $today->json('data.movement.steps'));
    }

    public function test_stats_and_guest_access(): void
    {
        Sanctum::actingAs($this->owner);
        $this->postJson('/api/v1/wellness/hydration', ['milliliters' => 1000])->assertCreated();
        $this->postJson('/api/v1/wellness/movement', ['minutes' => 20])->assertCreated();
        $stats = $this->getJson('/api/v1/wellness/stats');
        $stats->assertOk();
        $this->assertEquals(1000, $stats->json('data.total_milliliters'));
        $this->assertEquals(20, $stats->json('data.total_movement_minutes'));
        $this->getJson('/api/v1/wellness/today')->assertOk();
    }

    public function test_guest_cannot_access_wellness(): void
    {
        $this->getJson('/api/v1/wellness/today')->assertUnauthorized();
    }
}
