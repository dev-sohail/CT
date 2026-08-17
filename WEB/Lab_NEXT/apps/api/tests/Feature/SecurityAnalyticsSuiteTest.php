<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use Laravel\Sanctum\Sanctum;

class SecurityAnalyticsSuiteTest extends FeatureTestCase
{
    private User $owner;
    protected function setUp(): void { parent::setUp(); $this->owner = User::factory()->create(); }
    public function test_security_and_analytics_types_are_available(): void
    {
        Sanctum::actingAs($this->owner);
        foreach (['security_audits', 'devices', 'analytics', 'life_statistics', 'reports'] as $type) {
            $this->postJson("/api/v1/security-analytics/{$type}", ['name' => ucfirst($type), 'value' => 10])->assertCreated();
        }
        $summary = $this->getJson('/api/v1/security-analytics/analytics/summary');
        $summary->assertOk();
        $this->assertEquals(1, $summary->json('data.total'));
        $this->assertEquals(10, $summary->json('data.total_value'));
    }
    public function test_guest_cannot_access_security_analytics(): void { $this->getJson('/api/v1/security-analytics/reports')->assertUnauthorized(); }
}
