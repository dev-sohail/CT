<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\PersonalScorecardLifeKPITracker\Models\ScorecardMetric;
use Laravel\Sanctum\Sanctum;

class ScorecardTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_metric_crud_and_measurement_score(): void
    {
        Sanctum::actingAs($this->owner);
        $created = $this->postJson('/api/v1/scorecard', ['name' => 'Sleep', 'unit' => 'hours', 'target' => 8]);
        $created->assertCreated();
        $id = $created->json('data.id');
        $measure = $this->postJson("/api/v1/scorecard/{$id}/measure", ['value' => 7]);
        $measure->assertOk();
        $this->assertEquals(87.5, $measure->json('data.score'));
        $this->assertEquals(7, $this->getJson('/api/v1/scorecard')->json('data.0.latest.value'));
        $this->deleteJson("/api/v1/scorecard/{$id}")->assertNoContent();
    }

    public function test_lower_is_better_score_and_summary(): void
    {
        $metric = ScorecardMetric::create(['user_id' => $this->owner->id, 'name' => 'Screen time', 'target' => 4, 'direction' => 'lower']);
        Sanctum::actingAs($this->owner);
        $res = $this->postJson("/api/v1/scorecard/{$metric->id}/measure", ['value' => 5]);
        $res->assertOk();
        $this->assertEquals(80, $res->json('data.score'));
        $summary = $this->getJson('/api/v1/scorecard/summary');
        $summary->assertOk();
        $this->assertEquals(1, $summary->json('data.total'));
    }

    public function test_guest_cannot_access_scorecard(): void
    {
        $this->getJson('/api/v1/scorecard')->assertUnauthorized();
    }
}
