<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use Laravel\Sanctum\Sanctum;

class ExpenseTest extends FeatureTestCase
{
    private User $owner;
    protected function setUp(): void { parent::setUp(); $this->owner = User::factory()->create(); }
    public function test_expense_and_budget_summary(): void
    {
        Sanctum::actingAs($this->owner);
        $this->postJson('/api/v1/expenses', ['spent_on' => now()->toDateString(), 'description' => 'Groceries', 'category' => 'food', 'amount' => 80])->assertCreated();
        $this->postJson('/api/v1/expenses/budget', ['category' => 'food', 'amount' => 100, 'starts_on' => now()->startOfMonth()->toDateString()])->assertCreated();
        $summary = $this->getJson('/api/v1/expenses/budget-summary');
        $summary->assertOk();
        $this->assertEquals(80, $summary->json('data.0.spent'));
        $this->assertEquals(20, $summary->json('data.0.remaining'));
    }
    public function test_guest_cannot_access_expenses(): void { $this->getJson('/api/v1/expenses')->assertUnauthorized(); }
}
