<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\SymptomAndBodyMetricsTracker\Models\BodyObservation;
use Laravel\Sanctum\Sanctum;

class BodyObservationTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_observation_crud_and_trend(): void
    {
        Sanctum::actingAs($this->owner);
        $created = $this->postJson('/api/v1/body-observations', ['observed_at' => now()->toDateTimeString(), 'observation_type' => 'metric', 'name' => 'weight', 'value' => 80.5, 'unit' => 'kg']);
        $created->assertCreated();
        $id = $created->json('data.id');
        $trend = $this->getJson('/api/v1/body-observations/trend?name=weight');
        $trend->assertOk();
        $this->assertCount(1, $trend->json('data'));
        $this->deleteJson("/api/v1/body-observations/{$id}")->assertNoContent();
    }

    public function test_symptom_stats_and_filter(): void
    {
        BodyObservation::create(['user_id' => $this->owner->id, 'observed_at' => now(), 'observation_type' => 'symptom', 'name' => 'headache', 'severity' => 6]);
        BodyObservation::create(['user_id' => $this->owner->id, 'observed_at' => now(), 'observation_type' => 'metric', 'name' => 'weight', 'value' => 80]);
        Sanctum::actingAs($this->owner);
        $this->assertCount(1, $this->getJson('/api/v1/body-observations?observation_type=symptom')->json('data'));
        $stats = $this->getJson('/api/v1/body-observations/stats');
        $stats->assertOk();
        $this->assertEquals(2, $stats->json('data.total'));
        $this->assertEquals(6, $stats->json('data.average_symptom_severity'));
    }

    public function test_guest_cannot_access_observations(): void
    {
        $this->getJson('/api/v1/body-observations')->assertUnauthorized();
    }
}
