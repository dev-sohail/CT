<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\SleepAndRecoveryTracker\Models\SleepLog;
use Laravel\Sanctum\Sanctum;

class SleepLogTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_sleep_log_upserts_by_date_and_today_returns_it(): void
    {
        Sanctum::actingAs($this->owner);
        $date = now()->toDateString();
        $first = $this->postJson('/api/v1/sleep', ['sleep_date' => $date, 'duration_minutes' => 420, 'quality' => 7, 'interruptions' => 2]);
        $first->assertCreated();
        $second = $this->postJson('/api/v1/sleep', ['sleep_date' => $date, 'duration_minutes' => 450, 'quality' => 8]);
        $second->assertCreated();
        $this->assertDatabaseCount('sleep_logs', 1);
        $today = $this->getJson('/api/v1/sleep/today');
        $today->assertOk();
        $this->assertEquals(450, $today->json('data.duration_minutes'));
    }

    public function test_stats_and_filters(): void
    {
        SleepLog::create(['user_id' => $this->owner->id, 'sleep_date' => now()->toDateString(), 'duration_minutes' => 420, 'quality' => 8, 'interruptions' => 1]);
        SleepLog::create(['user_id' => $this->owner->id, 'sleep_date' => now()->subDay()->toDateString(), 'duration_minutes' => 360, 'quality' => 6, 'interruptions' => 2]);
        Sanctum::actingAs($this->owner);
        $stats = $this->getJson('/api/v1/sleep/stats');
        $stats->assertOk();
        $this->assertEquals(2, $stats->json('data.total_nights'));
        $this->assertEquals(390, $stats->json('data.average_duration_minutes'));
        $this->assertEquals(7, $stats->json('data.average_quality'));
        $this->assertEquals(3, $stats->json('data.total_interruptions'));
    }

    public function test_guest_cannot_access_sleep_logs(): void
    {
        $this->getJson('/api/v1/sleep')->assertUnauthorized();
    }
}
