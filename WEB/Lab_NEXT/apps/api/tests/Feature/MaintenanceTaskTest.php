<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use Laravel\Sanctum\Sanctum;

class MaintenanceTaskTest extends FeatureTestCase
{
    private User $owner;
    protected function setUp(): void { parent::setUp(); $this->owner = User::factory()->create(); }
    public function test_task_completion_and_stats(): void
    {
        Sanctum::actingAs($this->owner);
        $created = $this->postJson('/api/v1/maintenance', ['title' => 'Change filter', 'category' => 'HVAC', 'due_on' => now()->subDay()->toDateString(), 'cost' => 40]);
        $created->assertCreated();
        $id = $created->json('data.id');
        $this->postJson("/api/v1/maintenance/{$id}/complete")->assertOk();
        $stats = $this->getJson('/api/v1/maintenance/stats');
        $stats->assertOk();
        $this->assertEquals(1, $stats->json('data.completed'));
        $this->assertEquals(40, $stats->json('data.total_cost'));
    }
    public function test_guest_cannot_access_maintenance(): void { $this->getJson('/api/v1/maintenance')->assertUnauthorized(); }
}
