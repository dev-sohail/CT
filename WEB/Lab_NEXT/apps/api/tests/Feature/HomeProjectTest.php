<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use Laravel\Sanctum\Sanctum;

class HomeProjectTest extends FeatureTestCase
{
    private User $owner;
    protected function setUp(): void { parent::setUp(); $this->owner = User::factory()->create(); }
    public function test_project_completion_and_stats(): void
    {
        Sanctum::actingAs($this->owner);
        $created = $this->postJson('/api/v1/home-projects', ['name' => 'Paint kitchen', 'room' => 'Kitchen', 'budget' => 500, 'spent' => 100]);
        $created->assertCreated();
        $id = $created->json('data.id');
        $this->postJson("/api/v1/home-projects/{$id}/complete")->assertOk();
        $stats = $this->getJson('/api/v1/home-projects/stats');
        $stats->assertOk();
        $this->assertEquals(1, $stats->json('data.completed'));
        $this->assertEquals(500, $stats->json('data.budget'));
    }
    public function test_guest_cannot_access_projects(): void { $this->getJson('/api/v1/home-projects')->assertUnauthorized(); }
}
