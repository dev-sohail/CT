<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\ReceiptAndWarrantyArchive\Models\Receipt;
use Laravel\Sanctum\Sanctum;

class ReceiptTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_receipt_crud(): void
    {
        Sanctum::actingAs($this->owner);

        $created = $this->postJson('/api/v1/receipts', [
            'title' => 'TV purchase',
            'merchant' => 'BestBuy',
            'category' => 'electronics',
            'amount' => 899.99,
            'purchased_at' => now()->toDateString(),
            'warranty_until' => now()->addYears(2)->toDateString(),
            'items' => [['name' => 'TV', 'price' => 899.99]],
        ]);
        $created->assertCreated();
        $id = $created->json('data.id');
        $this->assertEquals('active', $created->json('data.warranty_status'));

        $updated = $this->putJson("/api/v1/receipts/{$id}", ['merchant' => 'Amazon']);
        $updated->assertOk();
        $this->assertEquals('Amazon', $updated->json('data.merchant'));

        $this->deleteJson("/api/v1/receipts/{$id}")->assertNoContent();
        $this->assertSoftDeleted('receipts', ['id' => $id]);
    }

    public function test_warranty_status_expiring_soon(): void
    {
        $receipt = Receipt::create(['user_id' => $this->owner->id, 'title' => 'Laptop', 'amount' => 1000, 'warranty_until' => now()->addDays(10)->toDateString()]);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson("/api/v1/receipts/{$receipt->id}");

        $res->assertOk();
        $this->assertEquals('expiring_soon', $res->json('data.warranty_status'));
    }

    public function test_warranties_endpoint_returns_only_future(): void
    {
        Receipt::create(['user_id' => $this->owner->id, 'title' => 'Active', 'amount' => 10, 'warranty_until' => now()->addMonths(3)->toDateString()]);
        Receipt::create(['user_id' => $this->owner->id, 'title' => 'Gone', 'amount' => 10, 'warranty_until' => now()->subDay()->toDateString()]);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/receipts/warranties');

        $res->assertOk();
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals('Active', $res->json('data.0.title'));
    }

    public function test_stats_totals(): void
    {
        Receipt::create(['user_id' => $this->owner->id, 'title' => 'R1', 'category' => 'groceries', 'amount' => 50]);
        Receipt::create(['user_id' => $this->owner->id, 'title' => 'R2', 'category' => 'groceries', 'amount' => 30, 'warranty_until' => now()->addDays(5)->toDateString()]);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/receipts/stats');

        $res->assertOk();
        $this->assertEquals(2, $res->json('data.total'));
        $this->assertEquals(80, $res->json('data.total_spent'));
        $this->assertEquals(80, $res->json('data.by_category.groceries'));
        $this->assertEquals(1, $res->json('data.warranties_expiring_soon'));
    }

    public function test_guest_cannot_access_receipts(): void
    {
        $this->getJson('/api/v1/receipts')->assertUnauthorized();
    }
}