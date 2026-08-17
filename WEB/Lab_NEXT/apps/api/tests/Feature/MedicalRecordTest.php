<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\MedicalHistoryAndVisitLog\Models\MedicalRecord;
use Laravel\Sanctum\Sanctum;

class MedicalRecordTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_medical_record_crud(): void
    {
        Sanctum::actingAs($this->owner);
        $created = $this->postJson('/api/v1/medical', ['record_type' => 'visit', 'record_date' => now()->toDateString(), 'title' => 'Annual checkup', 'provider' => 'Dr Smith', 'diagnosis' => 'Healthy']);
        $created->assertCreated();
        $id = $created->json('data.id');
        $this->assertEquals('Dr Smith', $created->json('data.provider'));
        $this->putJson("/api/v1/medical/{$id}", ['status' => 'resolved'])->assertOk();
        $this->deleteJson("/api/v1/medical/{$id}")->assertNoContent();
    }

    public function test_follow_ups_and_stats(): void
    {
        MedicalRecord::create(['user_id' => $this->owner->id, 'record_type' => 'visit', 'record_date' => now()->toDateString(), 'title' => 'Checkup', 'provider' => 'Dr A', 'follow_up_date' => now()->addDays(10)->toDateString()]);
        MedicalRecord::create(['user_id' => $this->owner->id, 'record_type' => 'condition', 'record_date' => now()->subMonth()->toDateString(), 'title' => 'Allergy', 'provider' => 'Dr A', 'status' => 'ongoing']);
        Sanctum::actingAs($this->owner);
        $this->assertCount(1, $this->getJson('/api/v1/medical/follow-ups')->json('data'));
        $stats = $this->getJson('/api/v1/medical/stats');
        $stats->assertOk();
        $this->assertEquals(2, $stats->json('data.total'));
        $this->assertEquals(1, $stats->json('data.visits'));
        $this->assertEquals(1, $stats->json('data.upcoming_follow_ups'));
    }

    public function test_guest_cannot_access_medical_records(): void
    {
        $this->getJson('/api/v1/medical')->assertUnauthorized();
    }
}
