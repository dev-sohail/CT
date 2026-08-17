<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\DigitalMediaLibrary\Models\MediaItem;
use Laravel\Sanctum\Sanctum;

class MediaLibraryTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_media_item_crud(): void
    {
        Sanctum::actingAs($this->owner);

        $created = $this->postJson('/api/v1/media', [
            'type' => 'book',
            'title' => 'The Pragmatic Programmer',
            'creator' => 'Hunt & Thomas',
            'year' => 1999,
            'status' => 'in_progress',
            'rating' => 9,
        ]);
        $created->assertCreated();
        $id = $created->json('data.id');
        $this->assertEquals('book', $created->json('data.type'));

        $updated = $this->putJson("/api/v1/media/{$id}", ['status' => 'finished', 'finished_at' => now()->toDateString()]);
        $updated->assertOk();
        $this->assertEquals('finished', $updated->json('data.status'));

        $this->deleteJson("/api/v1/media/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('media_items', ['id' => $id]);
    }

    public function test_filter_and_search(): void
    {
        MediaItem::create(['user_id' => $this->owner->id, 'type' => 'movie', 'title' => 'Inception', 'creator' => 'Nolan', 'status' => 'want']);
        MediaItem::create(['user_id' => $this->owner->id, 'type' => 'music', 'title' => 'Album X', 'status' => 'finished']);

        Sanctum::actingAs($this->owner);
        $this->assertCount(1, $this->getJson('/api/v1/media?type=movie')->json('data'));
        $this->assertCount(1, $this->getJson('/api/v1/media?q=nolan')->json('data'));
    }

    public function test_stats_aggregates(): void
    {
        MediaItem::create(['user_id' => $this->owner->id, 'type' => 'book', 'title' => 'A', 'status' => 'finished', 'rating' => 8]);
        MediaItem::create(['user_id' => $this->owner->id, 'type' => 'book', 'title' => 'B', 'status' => 'want', 'rating' => 4]);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/media/stats');

        $res->assertOk();
        $this->assertEquals(2, $res->json('data.total'));
        $this->assertEquals(2, $res->json('data.by_type.book'));
        $this->assertEquals(1, $res->json('data.finished'));
        $this->assertEquals(6, $res->json('data.average_rating'));
    }

    public function test_guest_cannot_access_media(): void
    {
        $this->getJson('/api/v1/media')->assertUnauthorized();
    }
}