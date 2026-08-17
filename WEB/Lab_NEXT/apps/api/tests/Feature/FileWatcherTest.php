<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\FileAutomationWatcher\Models\FileWatcherRule;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

class FileWatcherTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_rule_crud(): void
    {
        Sanctum::actingAs($this->owner);

        $created = $this->postJson('/api/v1/file-watcher/rules', [
            'name' => 'Sort PDFs',
            'source_path' => 'inbox',
            'pattern' => '*.pdf',
            'action' => 'move',
            'destination_path' => 'archive',
        ]);
        $created->assertCreated();
        $id = $created->json('data.id');
        $this->assertEquals('*.pdf', $created->json('data.pattern'));

        $shown = $this->getJson("/api/v1/file-watcher/rules/{$id}");
        $shown->assertOk();
        $this->assertEquals('Sort PDFs', $shown->json('data.name'));

        $updated = $this->putJson("/api/v1/file-watcher/rules/{$id}", ['pattern' => '*.doc']);
        $updated->assertOk();
        $this->assertEquals('*.doc', $updated->json('data.pattern'));

        $list = $this->getJson('/api/v1/file-watcher/rules');
        $list->assertOk();
        $this->assertCount(1, $list->json('data'));

        $this->deleteJson("/api/v1/file-watcher/rules/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('file_watcher_rules', ['id' => $id]);
    }

    public function test_scan_finds_matching_files(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('inbox/receipt.pdf', 'x');
        Storage::disk('local')->put('inbox/notes.txt', 'x');

        $rule = FileWatcherRule::create([
            'user_id' => $this->owner->id,
            'name' => 'PDFs',
            'source_path' => 'inbox',
            'pattern' => '*.pdf',
            'action' => 'move',
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->owner);
        $res = $this->postJson("/api/v1/file-watcher/rules/{$rule->id}/scan");

        $res->assertOk();
        $this->assertCount(1, $res->json('data.files'));
        $this->assertStringContainsString('receipt.pdf', $res->json('data.files.0'));
    }

    public function test_run_moves_matching_files(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('inbox/receipt.pdf', 'x');
        Storage::disk('local')->put('inbox/notes.txt', 'x');

        $rule = FileWatcherRule::create([
            'user_id' => $this->owner->id,
            'name' => 'PDFs',
            'source_path' => 'inbox',
            'pattern' => '*.pdf',
            'action' => 'move',
            'destination_path' => 'archive',
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->owner);
        $res = $this->postJson("/api/v1/file-watcher/rules/{$rule->id}/run");

        $res->assertOk();
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals('processed', $res->json('data.0.status'));

        $this->assertTrue(Storage::disk('local')->exists('archive/receipt.pdf'));
        $this->assertFalse(Storage::disk('local')->exists('inbox/receipt.pdf'));
        $this->assertTrue(Storage::disk('local')->exists('inbox/notes.txt'));
    }

    public function test_run_with_keyword_filter(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('inbox/tax-2025.pdf', 'x');
        Storage::disk('local')->put('inbox/receipt.pdf', 'x');

        $rule = FileWatcherRule::create([
            'user_id' => $this->owner->id,
            'name' => 'Tax',
            'source_path' => 'inbox',
            'pattern' => '*.pdf',
            'tag_keyword' => 'tax',
            'action' => 'move',
            'destination_path' => 'taxes',
            'is_active' => true,
        ]);

        $service = app(\App\Domains\FileAutomationWatcher\Services\FileWatcherService::class);
        $logs = $service->run($rule);

        $this->assertCount(1, $logs);
        $this->assertTrue(Storage::disk('local')->exists('taxes/tax-2025.pdf'));
        $this->assertTrue(Storage::disk('local')->exists('inbox/receipt.pdf'));
    }

    public function test_inactive_rule_scans_nothing(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('inbox/receipt.pdf', 'x');

        $rule = FileWatcherRule::create([
            'user_id' => $this->owner->id,
            'name' => 'Off',
            'source_path' => 'inbox',
            'action' => 'move',
            'is_active' => false,
        ]);

        Sanctum::actingAs($this->owner);
        $res = $this->postJson("/api/v1/file-watcher/rules/{$rule->id}/scan");

        $res->assertOk();
        $this->assertCount(0, $res->json('data.files'));
    }

    public function test_logs_are_recorded(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('inbox/doc.pdf', 'x');

        $rule = FileWatcherRule::create([
            'user_id' => $this->owner->id,
            'name' => 'PDFs',
            'source_path' => 'inbox',
            'pattern' => '*.pdf',
            'action' => 'delete',
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->owner);
        $this->postJson("/api/v1/file-watcher/rules/{$rule->id}/run")->assertOk();

        $logs = $this->getJson("/api/v1/file-watcher/rules/{$rule->id}/logs");
        $logs->assertOk();
        $this->assertCount(1, $logs->json('data'));
        $this->assertEquals('processed', $logs->json('data.0.status'));

        $this->assertFalse(Storage::disk('local')->exists('inbox/doc.pdf'));
    }

    public function test_guest_cannot_access_rules(): void
    {
        $this->getJson('/api/v1/file-watcher/rules')->assertUnauthorized();
    }
}