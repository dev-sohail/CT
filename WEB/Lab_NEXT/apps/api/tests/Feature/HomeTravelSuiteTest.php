<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use Laravel\Sanctum\Sanctum;

class HomeTravelSuiteTest extends FeatureTestCase
{
    private User $owner;
    protected function setUp(): void { parent::setUp(); $this->owner = User::factory()->create(); }
    public function test_home_and_travel_types_are_available(): void
    {
        Sanctum::actingAs($this->owner);
        foreach (['appliances', 'utilities', 'garden', 'inventory', 'trips', 'travel_journal', 'travel_documents', 'vehicles', 'routes'] as $type) {
            $this->postJson("/api/v1/home-travel/{$type}", ['name' => ucfirst($type)])->assertCreated();
        }
        $summary = $this->getJson('/api/v1/home-travel/trips/summary');
        $summary->assertOk();
        $this->assertEquals(1, $summary->json('data.total'));
    }
    public function test_guest_cannot_access_home_travel(): void { $this->getJson('/api/v1/home-travel/trips')->assertUnauthorized(); }
}
