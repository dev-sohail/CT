<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\DocumentArchiveAndVersionVault\Models\DocumentArchive;
use Laravel\Sanctum\Sanctum;

class DocumentArchiveTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_document_archive_crud(): void
    {
        Sanctum::actingAs($this->owner);

        $created = $this->postJson('/api/v1/document-archive', [
            'title' => 'Passport scan',
            'category' => 'identity',
            'tags' => ['travel', 'identity'],
            'expires_at' => now()->addYears(5)->toDateString(),
        ]);
        $created->assertCreated();
        $id = $created->json('data.id');
        $this->assertEquals('identity', $created->json('data.category'));
        $this->assertFalse($created->json('data.is_expired'));

        $updated = $this->putJson("/api/v1/document-archive/{$id}", ['status' => 'archived']);
        $updated->assertOk();
        $this->assertEquals('archived', $updated->json('data.status'));

        $this->deleteJson("/api/v1/document-archive/{$id}")->assertNoContent();
        $this->assertSoftDeleted('document_archives', ['id' => $id]);
    }

    public function test_expired_flag_and_filter(): void
    {
        DocumentArchive::create(['user_id' => $this->owner->id, 'title' => 'Old', 'expires_at' => now()->subDay(), 'status' => 'active']);
        DocumentArchive::create(['user_id' => $this->owner->id, 'title' => 'Live', 'status' => 'active']);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/document-archive');

        $res->assertOk();
        $old = collect($res->json('data'))->firstWhere('title', 'Old');
        $this->assertTrue($old['is_expired']);

        $filtered = $this->getJson('/api/v1/document-archive?status=archived');
        $this->assertCount(0, $filtered->json('data'));
    }

    public function test_search_and_tag_filter(): void
    {
        DocumentArchive::create(['user_id' => $this->owner->id, 'title' => 'Tax returns', 'tags' => ['finance']]);
        DocumentArchive::create(['user_id' => $this->owner->id, 'title' => 'Warranty card', 'tags' => ['home']]);

        Sanctum::actingAs($this->owner);
        $this->assertCount(1, $this->getJson('/api/v1/document-archive?q=tax')->json('data'));
        $this->assertCount(1, $this->getJson('/api/v1/document-archive?tag=home')->json('data'));
    }

    public function test_stats_reports_expiring_soon(): void
    {
        DocumentArchive::create(['user_id' => $this->owner->id, 'title' => 'S1', 'category' => 'insurance', 'expires_at' => now()->addDays(10)]);
        DocumentArchive::create(['user_id' => $this->owner->id, 'title' => 'S2', 'category' => 'insurance', 'expires_at' => now()->addYear()]);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/document-archive/stats');

        $res->assertOk();
        $this->assertEquals(2, $res->json('data.total'));
        $this->assertEquals(1, $res->json('data.expiring_soon'));
        $this->assertEquals(2, $res->json('data.by_category.insurance'));
    }

    public function test_guest_cannot_access_document_archive(): void
    {
        $this->getJson('/api/v1/document-archive')->assertUnauthorized();
    }
}