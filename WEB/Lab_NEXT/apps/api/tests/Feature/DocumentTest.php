<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\FileAndDocumentVault\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

class DocumentTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_upload_list_and_download(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/documents', [
            'file' => UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf'),
            'folder' => 'finance',
        ])->assertCreated()->assertJsonPath('data.name', 'invoice.pdf');

        $this->assertDatabaseCount('documents', 1);
        $this->assertDatabaseCount('document_versions', 1);

        $document = Document::first();
        $this->assertSame(1, $document->current_version);

        $this->getJson('/api/v1/documents')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson("/api/v1/documents/{$document->id}/download")
            ->assertOk();
    }

    public function test_versioning_flow(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/documents', [
            'file' => UploadedFile::fake()->create('v1.txt', 10),
        ])->assertCreated();

        $document = Document::first();

        $this->postJson("/api/v1/documents/{$document->id}/versions", [
            'file' => UploadedFile::fake()->create('v2.txt', 20),
        ])->assertCreated()->assertJsonPath('meta.version', 2);

        $this->getJson("/api/v1/documents/{$document->id}/versions")
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->assertSame(2, $document->fresh()->current_version);
    }

    public function test_soft_delete_and_restore(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/documents', [
            'file' => UploadedFile::fake()->create('temp.txt', 5),
        ])->assertCreated();

        $document = Document::first();

        $this->deleteJson("/api/v1/documents/{$document->id}")->assertNoContent();
        $this->assertSoftDeleted('documents', ['id' => $document->id]);

        $this->postJson("/api/v1/documents/{$document->id}/restore")->assertOk();
        $this->assertDatabaseHas('documents', ['id' => $document->id, 'deleted_at' => null]);
    }

    public function test_owner_boundary_on_show_and_download(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        Sanctum::actingAs($alice);
        $this->postJson('/api/v1/documents', [
            'file' => UploadedFile::fake()->create('alice.txt', 10),
        ])->assertCreated();
        $document = Document::first();

        Sanctum::actingAs($bob);
        $this->getJson("/api/v1/documents/{$document->id}")->assertNotFound();
        $this->getJson("/api/v1/documents/{$document->id}/download")->assertNotFound();
        $this->getJson('/api/v1/documents')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_upload_requires_file(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/documents', [])->assertUnprocessable();
    }
}
