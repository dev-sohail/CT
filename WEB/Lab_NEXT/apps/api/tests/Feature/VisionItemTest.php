<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\VisionBoardAndBucketListManager\Models\VisionItem;
use Laravel\Sanctum\Sanctum;

class VisionItemTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_vision_item_crud_and_completion(): void
    {
        Sanctum::actingAs($this->owner);
        $created = $this->postJson('/api/v1/vision', ['title' => 'Learn Spanish', 'type' => 'bucket_list', 'category' => 'travel', 'priority' => 'high']);
        $created->assertCreated();
        $id = $created->json('data.id');
        $done = $this->postJson("/api/v1/vision/{$id}/complete");
        $done->assertOk();
        $this->assertEquals('completed', $done->json('data.status'));
        $this->deleteJson("/api/v1/vision/{$id}")->assertNoContent();
    }

    public function test_board_filters_and_stats(): void
    {
        VisionItem::create(['user_id' => $this->owner->id, 'title' => 'A', 'type' => 'vision', 'category' => 'career']);
        VisionItem::create(['user_id' => $this->owner->id, 'title' => 'B', 'type' => 'bucket_list', 'category' => 'travel', 'status' => 'completed']);
        Sanctum::actingAs($this->owner);
        $this->assertCount(1, $this->getJson('/api/v1/vision?type=bucket_list')->json('data'));
        $board = $this->getJson('/api/v1/vision/board');
        $board->assertOk();
        $this->assertArrayHasKey('career', $board->json('data'));
        $stats = $this->getJson('/api/v1/vision/stats');
        $stats->assertOk();
        $this->assertEquals(50, $stats->json('data.completion_rate'));
    }

    public function test_guest_cannot_access_vision_items(): void
    {
        $this->getJson('/api/v1/vision')->assertUnauthorized();
    }
}
