<?php

namespace Tests\Feature;

use App\Domains\CertificationAndSkillRoadmap\Models\Certification;
use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use Laravel\Sanctum\Sanctum;

class CertificationTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_certification_crud(): void
    {
        Sanctum::actingAs($this->owner);

        $created = $this->postJson('/api/v1/certifications', [
            'title' => 'AWS Solutions Architect',
            'issuer' => 'Amazon',
            'status' => 'planned',
            'skills' => ['aws', 'cloud'],
        ]);
        $created->assertCreated();
        $id = $created->json('data.id');
        $this->assertEquals('AWS Solutions Architect', $created->json('data.title'));
        $this->assertEquals(['aws', 'cloud'], $created->json('data.skills'));

        $updated = $this->putJson("/api/v1/certifications/{$id}", ['status' => 'attained', 'issued_at' => '2026-08-01']);
        $updated->assertOk();
        $this->assertEquals('attained', $updated->json('data.status'));

        $this->deleteJson("/api/v1/certifications/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('certifications', ['id' => $id]);
    }

    public function test_expiring_soon_returns_certs(): void
    {
        Certification::create([
            'user_id' => $this->owner->id,
            'title' => 'Expiring',
            'status' => 'attained',
            'expiry_at' => now()->addDays(30)->toDateString(),
        ]);
        Certification::create([
            'user_id' => $this->owner->id,
            'title' => 'Far',
            'status' => 'attained',
            'expiry_at' => now()->addDays(300)->toDateString(),
        ]);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/certifications/expiring-soon');

        $res->assertOk();
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals('Expiring', $res->json('data.0.title'));
        $this->assertEquals(30, $res->json('data.0.days_until_expiry'));
    }

    public function test_stats_aggregates_skills(): void
    {
        Certification::create(['user_id' => $this->owner->id, 'title' => 'A', 'status' => 'attained', 'skills' => ['php', 'laravel']]);
        Certification::create(['user_id' => $this->owner->id, 'title' => 'B', 'status' => 'attained', 'skills' => ['php']]);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/certifications/stats');

        $res->assertOk();
        $this->assertEquals(2, $res->json('data.total'));
        $this->assertEquals(2, $res->json('data.skills.php'));
        $this->assertEquals(1, $res->json('data.skills.laravel'));
    }

    public function test_guest_cannot_access_certifications(): void
    {
        $this->getJson('/api/v1/certifications')->assertUnauthorized();
    }
}