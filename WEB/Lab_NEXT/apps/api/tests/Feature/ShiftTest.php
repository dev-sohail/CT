<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\WorkShiftAndScheduleManager\Models\Shift;
use Laravel\Sanctum\Sanctum;

class ShiftTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_shift_crud_and_earnings(): void
    {
        Sanctum::actingAs($this->owner);

        $created = $this->postJson('/api/v1/shifts', [
            'title' => 'Front desk',
            'starts_at' => '2026-09-01T09:00:00Z',
            'ends_at' => '2026-09-01T17:00:00Z',
            'hourly_rate' => 20,
            'client' => 'Hotel',
        ]);
        $created->assertCreated();
        $id = $created->json('data.id');
        $this->assertEquals(8, $created->json('data.duration_hours'));
        $this->assertEquals(160, $created->json('data.estimated_earnings'));

        $updated = $this->putJson("/api/v1/shifts/{$id}", ['status' => 'completed']);
        $updated->assertOk();
        $this->assertEquals('completed', $updated->json('data.status'));

        $this->deleteJson("/api/v1/shifts/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('shifts', ['id' => $id]);
    }

    public function test_conflict_detection_endpoint(): void
    {
        Shift::create([
            'user_id' => $this->owner->id,
            'title' => 'Existing',
            'starts_at' => '2026-09-05T10:00:00Z',
            'ends_at' => '2026-09-05T14:00:00Z',
            'status' => 'scheduled',
        ]);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/shifts/check-conflict?starts_at=2026-09-05T12:00:00Z&ends_at=2026-09-05T16:00:00Z');

        $res->assertOk();
        $this->assertTrue($res->json('data.has_conflict'));
        $this->assertCount(1, $res->json('data.conflicts'));
        $this->assertEquals('Existing', $res->json('data.conflicts.0.title'));
    }

    public function test_no_conflict_for_non_overlapping(): void
    {
        Shift::create([
            'user_id' => $this->owner->id,
            'title' => 'Existing',
            'starts_at' => '2026-09-05T10:00:00Z',
            'ends_at' => '2026-09-05T14:00:00Z',
            'status' => 'scheduled',
        ]);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/shifts/check-conflict?starts_at=2026-09-05T15:00:00Z&ends_at=2026-09-05T17:00:00Z');

        $res->assertOk();
        $this->assertFalse($res->json('data.has_conflict'));
    }

    public function test_shift_conflicts_relation(): void
    {
        $shift = Shift::create([
            'user_id' => $this->owner->id,
            'title' => 'A',
            'starts_at' => '2026-09-05T10:00:00Z',
            'ends_at' => '2026-09-05T14:00:00Z',
            'status' => 'scheduled',
        ]);
        Shift::create([
            'user_id' => $this->owner->id,
            'title' => 'B',
            'starts_at' => '2026-09-05T11:00:00Z',
            'ends_at' => '2026-09-05T12:00:00Z',
            'status' => 'scheduled',
        ]);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson("/api/v1/shifts/{$shift->id}/conflicts");

        $res->assertOk();
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals('B', $res->json('data.0.title'));
    }

    public function test_monthly_summary(): void
    {
        Shift::create([
            'user_id' => $this->owner->id,
            'title' => 'A',
            'starts_at' => now()->startOfMonth()->setTime(9, 0),
            'ends_at' => now()->startOfMonth()->setTime(11, 0),
            'hourly_rate' => 25,
        ]);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/shifts/monthly');

        $res->assertOk();
        $this->assertEquals(1, $res->json('data.shift_count'));
        $this->assertEquals(2, $res->json('data.total_hours'));
        $this->assertEquals(50, $res->json('data.estimated_earnings'));
    }

    public function test_guest_cannot_access_shifts(): void
    {
        $this->getJson('/api/v1/shifts')->assertUnauthorized();
    }
}