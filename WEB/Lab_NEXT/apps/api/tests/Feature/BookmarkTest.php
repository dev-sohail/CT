<?php

namespace Tests\Feature;

use App\Domains\BookmarkAndReadLaterArchive\Models\Bookmark;
use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use Laravel\Sanctum\Sanctum;

class BookmarkTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_bookmark_crud(): void
    {
        Sanctum::actingAs($this->owner);

        $created = $this->postJson('/api/v1/bookmarks', [
            'url' => 'https://example.com/article',
            'title' => 'Great article',
            'tags' => ['dev', 'reading'],
        ]);
        $created->assertCreated();
        $id = $created->json('data.id');
        $this->assertEquals('example.com', $created->json('data.domain'));
        $this->assertEquals(['dev', 'reading'], $created->json('data.tags'));

        $updated = $this->putJson("/api/v1/bookmarks/{$id}", ['status' => 'read']);
        $updated->assertOk();
        $this->assertEquals('read', $updated->json('data.status'));

        $this->deleteJson("/api/v1/bookmarks/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('bookmarks', ['id' => $id]);
    }

    public function test_search_finds_content_and_title(): void
    {
        Bookmark::create(['user_id' => $this->owner->id, 'url' => 'https://a.dev/x', 'title' => 'Laravel tips', 'saved_content' => 'Middleware and routing']);
        Bookmark::create(['user_id' => $this->owner->id, 'url' => 'https://b.dev/y', 'title' => 'Recipes']);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/bookmarks?q=middleware');

        $res->assertOk();
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals('Laravel tips', $res->json('data.0.title'));
    }

    public function test_filter_by_tag_and_status(): void
    {
        Bookmark::create(['user_id' => $this->owner->id, 'url' => 'https://a.dev/x', 'title' => 'A', 'tags' => ['dev'], 'status' => 'unread']);
        Bookmark::create(['user_id' => $this->owner->id, 'url' => 'https://b.dev/y', 'title' => 'B', 'tags' => ['personal'], 'status' => 'read']);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/bookmarks?tag=dev');

        $res->assertOk();
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals('A', $res->json('data.0.title'));
    }

    public function test_stats_aggregates_by_domain(): void
    {
        Bookmark::create(['user_id' => $this->owner->id, 'url' => 'https://a.dev/x', 'title' => 'A', 'domain' => 'a.dev', 'status' => 'unread']);
        Bookmark::create(['user_id' => $this->owner->id, 'url' => 'https://a.dev/y', 'title' => 'B', 'domain' => 'a.dev', 'status' => 'read']);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/bookmarks/stats');

        $res->assertOk();
        $this->assertEquals(2, $res->json('data.total'));
        $this->assertEquals(2, $res->json('data.by_domain')['a.dev']);
        $this->assertEquals(1, $res->json('data.unread'));
    }

    public function test_guest_cannot_access_bookmarks(): void
    {
        $this->getJson('/api/v1/bookmarks')->assertUnauthorized();
    }
}