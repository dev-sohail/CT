<?php

namespace Tests\Feature;

use App\Domains\AuditTrailAndActivityTimeline\Services\AuditService;
use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\SecondBrainPersonalWiki\Models\Page;
use Laravel\Sanctum\Sanctum;

class AuditTest extends FeatureTestCase
{
    public function test_login_records_audit_log(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'auth.login',
            'subject_type' => $user->getMorphClass(),
            'subject_id' => $user->id,
        ]);
    }

    public function test_audit_logs_are_scoped_to_owner(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        app(AuditService::class)->record($user, 'test.action', null, ['hello' => 'world']);

        Sanctum::actingAs($user);
        $this->getJson('/api/v1/audit-log')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.action', 'test.action');

        Sanctum::actingAs($other);
        $this->getJson('/api/v1/audit-log')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_audit_log_filter_and_pagination(): void
    {
        $user = User::factory()->create();
        $service = app(AuditService::class);

        $service->record($user, 'page.create');
        $service->record($user, 'page.update');
        $service->record($user, 'task.delete');

        Sanctum::actingAs($user);
        $this->getJson('/api/v1/audit-log?action=page.create')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/v1/audit-log?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data', 'meta' => ['pagination' => []]]);
    }

    public function test_audit_service_records_subject(): void
    {
        $user = User::factory()->create();
        $page = new Page(['title' => 'X']);
        $page->forceFill(['id' => 99])->setRelation('section', null);
        $page->exists = true;

        $log = app(AuditService::class)->record($user, 'page.destroy', $page, ['soft' => true]);

        $this->assertEquals(Page::class, $log->subject_type);
        $this->assertEquals(99, $log->subject_id);
        $this->assertEquals(['soft' => true], $log->meta);
        $this->assertDatabaseHas('audit_logs', ['id' => $log->id]);
    }
}
