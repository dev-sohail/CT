<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use Laravel\Sanctum\Sanctum;

class FinanceSuiteTest extends FeatureTestCase
{
    private User $owner;
    protected function setUp(): void { parent::setUp(); $this->owner = User::factory()->create(); }
    public function test_typed_finance_records_cover_bills_and_savings(): void
    {
        Sanctum::actingAs($this->owner);
        $bill = $this->postJson('/api/v1/finance/bills', ['name' => 'Electricity', 'amount' => 90, 'due_date' => now()->addDays(3)->toDateString()]);
        $bill->assertCreated();
        $this->postJson('/api/v1/finance/savings', ['name' => 'Emergency fund', 'target_amount' => 10000, 'current_amount' => 2500])->assertCreated();
        $summary = $this->getJson('/api/v1/finance/bills/summary');
        $summary->assertOk();
        $this->assertEquals(1, $summary->json('data.total'));
        $this->assertEquals(90, $summary->json('data.total_amount'));
        $this->assertCount(1, $this->getJson('/api/v1/finance/bills')->json('data'));
    }
    public function test_all_finance_types_are_supported(): void
    {
        Sanctum::actingAs($this->owner);
        foreach (['investments', 'loans', 'insurance', 'tax_documents', 'purchases'] as $type) {
            $this->postJson("/api/v1/finance/{$type}", ['name' => ucfirst($type)])->assertCreated();
        }
        $this->assertEquals(1, $this->getJson('/api/v1/finance/purchases/summary')->json('data.total'));
    }
    public function test_guest_cannot_access_finance(): void { $this->getJson('/api/v1/finance/bills')->assertUnauthorized(); }
}
