<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\SubscriptionAndRecurringPaymentTracker\Models\Subscription;
use Laravel\Sanctum\Sanctum;

class SubscriptionTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_subscription_crud(): void
    {
        Sanctum::actingAs($this->owner);

        $created = $this->postJson('/api/v1/subscriptions', [
            'name' => 'Netflix',
            'category' => 'entertainment',
            'amount' => 15.99,
            'billing_cycle' => 'monthly',
            'next_billing_at' => now()->addMonth()->toDateString(),
        ]);
        $created->assertCreated();
        $id = $created->json('data.id');
        $this->assertEquals(15.99, $created->json('data.amount'));
        $this->assertEquals(15.99, $created->json('data.monthly_equivalent'));

        $updated = $this->putJson("/api/v1/subscriptions/{$id}", ['status' => 'cancelled']);
        $updated->assertOk();
        $this->assertEquals('cancelled', $updated->json('data.status'));

        $this->deleteJson("/api/v1/subscriptions/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('subscriptions', ['id' => $id]);
    }

    public function test_monthly_equivalent_for_yearly_cycle(): void
    {
        Sanctum::actingAs($this->owner);

        $created = $this->postJson('/api/v1/subscriptions', [
            'name' => 'Amazon Prime',
            'amount' => 120,
            'billing_cycle' => 'yearly',
        ]);

        $created->assertCreated();
        $this->assertEquals(10, $created->json('data.monthly_equivalent'));
    }

    public function test_upcoming_filter(): void
    {
        Subscription::create(['user_id' => $this->owner->id, 'name' => 'Soon', 'status' => 'active', 'next_billing_at' => now()->addDays(3)->toDateString()]);
        Subscription::create(['user_id' => $this->owner->id, 'name' => 'Later', 'status' => 'active', 'next_billing_at' => now()->addDays(60)->toDateString()]);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/subscriptions?upcoming=7');

        $res->assertOk();
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals('Soon', $res->json('data.0.name'));
    }

    public function test_stats_calculates_costs(): void
    {
        Subscription::create(['user_id' => $this->owner->id, 'name' => 'A', 'category' => 'software', 'amount' => 10, 'billing_cycle' => 'monthly', 'status' => 'active']);
        Subscription::create(['user_id' => $this->owner->id, 'name' => 'B', 'category' => 'software', 'amount' => 120, 'billing_cycle' => 'yearly', 'status' => 'active']);
        Subscription::create(['user_id' => $this->owner->id, 'name' => 'C', 'category' => 'gym', 'amount' => 30, 'billing_cycle' => 'monthly', 'status' => 'cancelled']);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/subscriptions/stats');

        $res->assertOk();
        $this->assertEquals(3, $res->json('data.total'));
        $this->assertEquals(2, $res->json('data.active'));
        $this->assertEquals(20, $res->json('data.monthly_cost'));
        $this->assertEquals(240, $res->json('data.yearly_cost'));
        $this->assertEquals(20, $res->json('data.by_category.software.monthly'));
    }

    public function test_guest_cannot_access_subscriptions(): void
    {
        $this->getJson('/api/v1/subscriptions')->assertUnauthorized();
    }
}