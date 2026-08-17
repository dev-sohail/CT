<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\ResearchAndPDFAnnotationManager\Models\Paper;
use Laravel\Sanctum\Sanctum;

class PaperTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_paper_crud(): void
    {
        Sanctum::actingAs($this->owner);

        $created = $this->postJson('/api/v1/papers', [
            'title' => 'Attention Is All You Need',
            'authors' => ['Vaswani', 'Shazeer'],
            'year' => '2017',
            'source' => 'arxiv',
            'status' => 'reading',
        ]);
        $created->assertCreated();
        $id = $created->json('data.id');
        $this->assertEquals(['Vaswani', 'Shazeer'], $created->json('data.authors'));

        $updated = $this->putJson("/api/v1/papers/{$id}", ['status' => 'read', 'rating' => 5]);
        $updated->assertOk();
        $this->assertEquals('read', $updated->json('data.status'));
        $this->assertEquals(5, $updated->json('data.rating'));

        $this->deleteJson("/api/v1/papers/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('papers', ['id' => $id]);
    }

    public function test_annotations_flow(): void
    {
        $paper = Paper::create(['user_id' => $this->owner->id, 'title' => 'Paper']);

        Sanctum::actingAs($this->owner);
        $added = $this->postJson("/api/v1/papers/{$paper->id}/annotations", [
            'page' => 3,
            'text' => 'Key insight',
            'color' => 'yellow',
        ]);
        $added->assertCreated();
        $annId = $added->json('data.id');
        $this->assertEquals('Key insight', $added->json('data.text'));

        $updated = $this->putJson("/api/v1/papers/{$paper->id}/annotations/{$annId}", ['note' => 'quoted in ch2']);
        $updated->assertOk();
        $this->assertEquals('quoted in ch2', $updated->json('data.note'));

        $list = $this->getJson("/api/v1/papers/{$paper->id}/annotations");
        $list->assertOk();
        $this->assertCount(1, $list->json('data'));

        $this->deleteJson("/api/v1/papers/{$paper->id}/annotations/{$annId}")->assertNoContent();
        $this->assertDatabaseMissing('paper_annotations', ['id' => $annId]);
    }

    public function test_search_filters_by_status_and_term(): void
    {
        Paper::create(['user_id' => $this->owner->id, 'title' => 'Deep Learning', 'status' => 'unread']);
        Paper::create(['user_id' => $this->owner->id, 'title' => 'LLMs', 'status' => 'read']);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/papers?status=read');

        $res->assertOk();
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals('LLMs', $res->json('data.0.title'));
    }

    public function test_guest_cannot_access_papers(): void
    {
        $this->getJson('/api/v1/papers')->assertUnauthorized();
    }
}