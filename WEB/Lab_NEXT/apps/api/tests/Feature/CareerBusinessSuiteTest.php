<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use Laravel\Sanctum\Sanctum;

class CareerBusinessSuiteTest extends FeatureTestCase
{
    private User $owner;
    protected function setUp(): void { parent::setUp(); $this->owner = User::factory()->create(); }
    public function test_career_and_business_types_are_available(): void
    {
        Sanctum::actingAs($this->owner);
        foreach (['resumes', 'job_applications', 'achievements', 'meetings', 'invoices', 'clients', 'relationships', 'follow_ups', 'templates', 'important_dates', 'gifts', 'family_records'] as $type) {
            $this->postJson("/api/v1/career/{$type}", ['title' => ucfirst($type)])->assertCreated();
        }
        $summary = $this->getJson('/api/v1/career/job_applications/summary');
        $summary->assertOk();
        $this->assertEquals(1, $summary->json('data.total'));
    }
    public function test_guest_cannot_access_career_records(): void { $this->getJson('/api/v1/career/resumes')->assertUnauthorized(); }
}
