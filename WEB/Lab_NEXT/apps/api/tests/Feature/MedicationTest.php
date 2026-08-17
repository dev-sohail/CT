<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\MedicationAndPrescriptionTracker\Models\Medication;
use Laravel\Sanctum\Sanctum;

class MedicationTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_medication_crud_and_adherence(): void
    {
        Sanctum::actingAs($this->owner);
        $created = $this->postJson('/api/v1/medications', ['name' => 'Vitamin D', 'dosage' => '1000 IU', 'frequency' => 'daily']);
        $created->assertCreated();
        $id = $created->json('data.id');
        $this->postJson("/api/v1/medications/{$id}/log", ['status' => 'taken'])->assertOk();
        $this->postJson("/api/v1/medications/{$id}/log", ['status' => 'missed'])->assertOk();
        $adherence = $this->getJson("/api/v1/medications/{$id}/adherence");
        $adherence->assertOk();
        $this->assertEquals(50, $adherence->json('data.adherence_rate'));
        $this->deleteJson("/api/v1/medications/{$id}")->assertNoContent();
    }

    public function test_stats_and_status_filter(): void
    {
        Medication::create(['user_id' => $this->owner->id, 'name' => 'A', 'frequency' => 'daily', 'status' => 'active']);
        Medication::create(['user_id' => $this->owner->id, 'name' => 'B', 'frequency' => 'weekly', 'status' => 'paused']);
        Sanctum::actingAs($this->owner);
        $this->assertCount(1, $this->getJson('/api/v1/medications?status=paused')->json('data'));
        $stats = $this->getJson('/api/v1/medications/stats');
        $stats->assertOk();
        $this->assertEquals(2, $stats->json('data.total'));
        $this->assertEquals(1, $stats->json('data.active'));
    }

    public function test_guest_cannot_access_medications(): void
    {
        $this->getJson('/api/v1/medications')->assertUnauthorized();
    }
}
