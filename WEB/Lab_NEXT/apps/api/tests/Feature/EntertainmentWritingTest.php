<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use Laravel\Sanctum\Sanctum;

class EntertainmentWritingTest extends FeatureTestCase
{
    private User $owner;
    protected function setUp(): void { parent::setUp(); $this->owner = User::factory()->create(); }
    public function test_entertainment_and_writing_types_are_available(): void
    {
        Sanctum::actingAs($this->owner);
        foreach (['watch', 'reading', 'music', 'chess', 'wishlist', 'writing', 'publishing', 'ideas'] as $type) {
            $this->postJson("/api/v1/entertainment/{$type}", ['title' => ucfirst($type), 'rating' => 8])->assertCreated();
        }
        $stats = $this->getJson('/api/v1/entertainment/reading/stats');
        $stats->assertOk();
        $this->assertEquals(1, $stats->json('data.total'));
        $this->assertEquals(8, $stats->json('data.average_rating'));
    }
    public function test_guest_cannot_access_entertainment(): void { $this->getJson('/api/v1/entertainment/reading')->assertUnauthorized(); }
}
