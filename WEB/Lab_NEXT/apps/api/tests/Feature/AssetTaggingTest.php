<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\QRAssetTaggingSystem\Models\Asset;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

class AssetTaggingTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_asset_crud_generates_qr_token(): void
    {
        Sanctum::actingAs($this->owner);

        $created = $this->postJson('/api/v1/assets', [
            'name' => 'Laptop',
            'category' => 'electronics',
            'location' => 'Home office',
            'value' => 1200,
            'warranty_until' => now()->addYear()->toDateString(),
        ]);
        $created->assertCreated();
        $id = $created->json('data.id');
        $this->assertNotNull($created->json('data.qr_token'));
        $this->assertStringStartsWith('ctlab://scan/asset/', $created->json('data.qr_payload'));

        $this->deleteJson("/api/v1/assets/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('assets', ['id' => $id]);
    }

    public function test_scan_by_payload_returns_asset_and_updates_location(): void
    {
        $asset = Asset::create(['user_id' => $this->owner->id, 'name' => 'Bike', 'qr_token' => Str::random(24), 'location' => 'Garage']);

        Sanctum::actingAs($this->owner);
        $res = $this->postJson('/api/v1/assets/scan', [
            'payload' => "ctlab://scan/asset/{$asset->qr_token}",
            'location' => 'Shed',
        ]);

        $res->assertOk();
        $this->assertEquals('Bike', $res->json('data.name'));
        $this->assertEquals('Shed', $res->json('data.location'));
    }

    public function test_scan_unknown_payload_returns_404(): void
    {
        Sanctum::actingAs($this->owner);
        $this->postJson('/api/v1/assets/scan', ['payload' => 'ctlab://scan/asset/unknown'])->assertNotFound();
    }

    public function test_qr_endpoint_returns_payload(): void
    {
        $asset = Asset::create(['user_id' => $this->owner->id, 'name' => 'Camera', 'qr_token' => Str::random(24)]);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson("/api/v1/assets/{$asset->id}/qr");

        $res->assertOk();
        $this->assertEquals("ctlab://scan/asset/{$asset->qr_token}", $res->json('data.payload'));
    }

    public function test_stats_includes_value_and_warranty(): void
    {
        Asset::create(['user_id' => $this->owner->id, 'name' => 'A', 'category' => 'electronics', 'value' => 100, 'warranty_until' => now()->addDays(5)]);
        Asset::create(['user_id' => $this->owner->id, 'name' => 'B', 'category' => 'furniture', 'value' => 50]);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/assets/stats');

        $res->assertOk();
        $this->assertEquals(2, $res->json('data.total'));
        $this->assertEquals(150, $res->json('data.total_value'));
        $this->assertEquals(1, $res->json('data.warranty_expiring_soon'));
    }

    public function test_guest_cannot_access_assets(): void
    {
        $this->getJson('/api/v1/assets')->assertUnauthorized();
    }
}