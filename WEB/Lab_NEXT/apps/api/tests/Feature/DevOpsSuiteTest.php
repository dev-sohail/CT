<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use Laravel\Sanctum\Sanctum;

class DevOpsSuiteTest extends FeatureTestCase
{
    private User $owner;
    protected function setUp(): void { parent::setUp(); $this->owner = User::factory()->create(); }
    public function test_devops_types_are_available(): void
    {
        Sanctum::actingAs($this->owner);
        foreach (['api_testing', 'scaffolding', 'schemas', 'packages', 'environments', 'deployments', 'containers', 'servers', 'monitoring', 'certificates', 'docs'] as $type) {
            $this->postJson("/api/v1/devops/{$type}", ['name' => ucfirst($type)])->assertCreated();
        }
        $stats = $this->getJson('/api/v1/devops/deployments/stats');
        $stats->assertOk();
        $this->assertEquals(1, $stats->json('data.total'));
    }
    public function test_guest_cannot_access_devops(): void { $this->getJson('/api/v1/devops/servers')->assertUnauthorized(); }
}
