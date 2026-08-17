<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\DecisionJournal\Models\Decision;
use Laravel\Sanctum\Sanctum;

class DecisionTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_decision_crud_and_decide_transition(): void
    {
        Sanctum::actingAs($this->owner);
        $created = $this->postJson('/api/v1/decisions', ['title' => 'Choose stack', 'options' => ['A', 'B'], 'confidence' => 7]);
        $created->assertCreated();
        $id = $created->json('data.id');
        $updated = $this->putJson("/api/v1/decisions/{$id}", ['status' => 'decided', 'decision' => 'A', 'rationale' => 'Faster']);
        $updated->assertOk();
        $this->assertEquals('decided', $updated->json('data.status'));
        $this->assertNotNull($updated->json('data.decided_at'));
        $this->deleteJson("/api/v1/decisions/{$id}")->assertNoContent();
    }

    public function test_due_reviews_and_stats(): void
    {
        Decision::create(['user_id' => $this->owner->id, 'title' => 'Due', 'status' => 'decided', 'confidence' => 8, 'review_at' => now()->subDay()]);
        Decision::create(['user_id' => $this->owner->id, 'title' => 'Later', 'status' => 'open', 'confidence' => 6, 'review_at' => now()->addDay()]);
        Sanctum::actingAs($this->owner);
        $due = $this->getJson('/api/v1/decisions/due-reviews');
        $due->assertOk();
        $this->assertCount(1, $due->json('data'));
        $stats = $this->getJson('/api/v1/decisions/stats');
        $stats->assertOk();
        $this->assertEquals(7, $stats->json('data.average_confidence'));
        $this->assertEquals(1, $stats->json('data.due_reviews'));
    }

    public function test_guest_cannot_access_decisions(): void
    {
        $this->getJson('/api/v1/decisions')->assertUnauthorized();
    }
}
