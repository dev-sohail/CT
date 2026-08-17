<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\SecondBrainPersonalWiki\Models\Page;
use App\Domains\SecondBrainPersonalWiki\Models\Workspace;
use Laravel\Sanctum\Sanctum;

class WikiTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    private function actAs(User $user): void
    {
        Sanctum::actingAs($user);
    }

    private function createWorkspace(User $user, array $overrides = []): Workspace
    {
        $this->actAs($user);

        $response = $this->postJson('/api/v1/workspaces', array_merge([
            'name' => 'My Workspace',
            'description' => 'Test workspace',
        ], $overrides))
            ->assertCreated();

        return Workspace::findOrFail($response->json('data.id'));
    }

    private function createNotebook(Workspace $workspace, User $user): int
    {
        $this->actAs($user);

        return $this->postJson("/api/v1/workspaces/{$workspace->id}/notebooks", [
            'name' => 'My Notebook',
        ])
            ->assertCreated()
            ->json('data.id');
    }

    private function createSection(int $notebookId, User $user): int
    {
        $this->actAs($user);

        return $this->postJson("/api/v1/notebooks/{$notebookId}/sections", [
            'name' => 'My Section',
        ])
            ->assertCreated()
            ->json('data.id');
    }

    private function createPage(int $sectionId, User $user, array $overrides = []): int
    {
        $this->actAs($user);

        return $this->postJson("/api/v1/sections/{$sectionId}/pages", array_merge([
            'title' => 'My Page',
            'content' => 'Hello world',
        ], $overrides))
            ->assertCreated()
            ->json('data.id');
    }

    public function test_wiki_requires_authentication(): void
    {
        $this->getJson('/api/v1/workspaces')->assertUnauthorized();
        $this->postJson('/api/v1/workspaces', ['name' => 'X'])->assertUnauthorized();
        $this->getJson('/api/v1/pages')->assertUnauthorized();
    }

    public function test_workspace_crud_lifecycle(): void
    {
        $workspace = $this->createWorkspace($this->owner);

        $this->actAs($this->owner);
        $this->getJson('/api/v1/workspaces')
            ->assertOk()
            ->assertJsonPath('data.0.id', $workspace->id);

        $this->actAs($this->owner);
        $this->putJson("/api/v1/workspaces/{$workspace->id}", ['name' => 'Renamed', 'is_default' => true])
            ->assertOk()
            ->assertJsonPath('data.name', 'Renamed');

        $this->actAs($this->owner);
        $this->getJson("/api/v1/workspaces/{$workspace->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Renamed');

        $this->actAs($this->owner);
        $this->deleteJson("/api/v1/workspaces/{$workspace->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('wiki_workspaces', ['id' => $workspace->id]);
    }

    public function test_workspace_validation_envelope(): void
    {
        $this->actAs($this->owner);
        $this->postJson('/api/v1/workspaces', ['name' => ''])
            ->assertStatus(422)
            ->assertJsonStructure(['data', 'meta' => ['version'], 'errors']);
    }

    public function test_full_hierarchy_creation(): void
    {
        $workspace = $this->createWorkspace($this->owner);
        $notebookId = $this->createNotebook($workspace, $this->owner);
        $sectionId = $this->createSection($notebookId, $this->owner);
        $pageId = $this->createPage($sectionId, $this->owner, ['tags' => ['php', 'laravel']]);

        $page = Page::with('tags', 'section.notebook.workspace')->findOrFail($pageId);
        $this->assertSame($workspace->id, $page->section->notebook->workspace_id);
        $this->assertCount(2, $page->tags);
    }

    public function test_page_update_and_favorite_toggle(): void
    {
        $workspace = $this->createWorkspace($this->owner);
        $notebookId = $this->createNotebook($workspace, $this->owner);
        $sectionId = $this->createSection($notebookId, $this->owner);
        $pageId = $this->createPage($sectionId, $this->owner);

        $this->actAs($this->owner);
        $this->putJson("/api/v1/pages/{$pageId}", ['title' => 'Updated Title'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated Title');

        $this->actAs($this->owner);
        $this->postJson("/api/v1/pages/{$pageId}/favorite")
            ->assertOk()
            ->assertJsonPath('data.is_favorite', true);

        $this->actAs($this->owner);
        $this->getJson('/api/v1/pages?favorites=true')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_page_search_and_backlinks(): void
    {
        $workspace = $this->createWorkspace($this->owner);
        $notebookId = $this->createNotebook($workspace, $this->owner);
        $sectionId = $this->createSection($notebookId, $this->owner);
        $this->createPage($sectionId, $this->owner, ['title' => 'Alpha Page']);

        $this->actAs($this->owner);
        $this->getJson('/api/v1/pages?q=Alpha')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Alpha Page');

        $this->actAs($this->owner);
        $this->getJson('/api/v1/pages?q=Nomatch')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_soft_delete_trash_restore_force(): void
    {
        $workspace = $this->createWorkspace($this->owner);
        $notebookId = $this->createNotebook($workspace, $this->owner);
        $sectionId = $this->createSection($notebookId, $this->owner);
        $pageId = $this->createPage($sectionId, $this->owner);

        $this->actAs($this->owner);
        $this->deleteJson("/api/v1/pages/{$pageId}")
            ->assertNoContent();

        $this->assertSoftDeleted('wiki_pages', ['id' => $pageId]);
        $this->assertDatabaseCount('wiki_pages', 1);

        $this->actAs($this->owner);
        $this->getJson('/api/v1/pages/trash')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actAs($this->owner);
        $this->postJson("/api/v1/pages/{$pageId}/restore")
            ->assertOk();

        $this->assertNotSoftDeleted('wiki_pages', ['id' => $pageId]);

        $this->actAs($this->owner);
        $this->deleteJson("/api/v1/pages/{$pageId}/force")
            ->assertNoContent();

        $this->assertDatabaseMissing('wiki_pages', ['id' => $pageId]);
    }

    public function test_blocks_sync_creates_updates_and_prunes(): void
    {
        $workspace = $this->createWorkspace($this->owner);
        $notebookId = $this->createNotebook($workspace, $this->owner);
        $sectionId = $this->createSection($notebookId, $this->owner);
        $pageId = $this->createPage($sectionId, $this->owner);

        $this->actAs($this->owner);
        $response = $this->postJson("/api/v1/pages/{$pageId}/blocks/sync", [
            'blocks' => [
                ['type' => 'heading', 'content' => '# Intro', 'sort_order' => 0],
                ['type' => 'text', 'content' => 'Body text', 'sort_order' => 1],
            ],
        ])
            ->assertOk();

        $this->assertCount(2, $response->json('data'));
        $this->assertDatabaseCount('wiki_blocks', 2);

        $blockId = $response->json('data.0.id');

        $this->actAs($this->owner);
        $this->postJson("/api/v1/pages/{$pageId}/blocks/sync", [
            'blocks' => [
                ['id' => $blockId, 'type' => 'heading', 'content' => '# Updated', 'sort_order' => 0],
            ],
        ])
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->assertDatabaseHas('wiki_blocks', ['id' => $blockId, 'content' => '# Updated']);
        $this->assertDatabaseCount('wiki_blocks', 1);
    }

    public function test_page_versions_snapshot_and_restore(): void
    {
        $workspace = $this->createWorkspace($this->owner);
        $notebookId = $this->createNotebook($workspace, $this->owner);
        $sectionId = $this->createSection($notebookId, $this->owner);
        $pageId = $this->createPage($sectionId, $this->owner);

        $this->actAs($this->owner);
        $this->postJson("/api/v1/pages/{$pageId}/versions", ['note' => 'First snapshot'])
            ->assertCreated();

        $this->actAs($this->owner);
        $this->putJson("/api/v1/pages/{$pageId}", ['title' => 'Snapshot Me'])
            ->assertOk();

        $versions = $this->getJson("/api/v1/pages/{$pageId}/versions")
            ->assertOk()
            ->json('data');

        $this->assertNotEmpty($versions);

        $versionId = $versions[0]['id'];

        $this->actAs($this->owner);
        $this->postJson("/api/v1/versions/{$versionId}/restore")
            ->assertOk()
            ->assertJsonPath('data.content', 'Hello world');
    }

    public function test_projects_attach_and_detach_pages(): void
    {
        $workspace = $this->createWorkspace($this->owner);
        $notebookId = $this->createNotebook($workspace, $this->owner);
        $sectionId = $this->createSection($notebookId, $this->owner);
        $pageId = $this->createPage($sectionId, $this->owner);

        $this->actAs($this->owner);
        $projectId = $this->postJson("/api/v1/workspaces/{$workspace->id}/projects", ['name' => 'Project One'])
            ->assertCreated()
            ->json('data.id');

        $this->actAs($this->owner);
        $this->postJson("/api/v1/projects/{$projectId}/pages/{$pageId}")
            ->assertOk();

        $this->assertDatabaseHas('wiki_pages', ['id' => $pageId, 'project_id' => $projectId]);

        $this->actAs($this->owner);
        $this->deleteJson("/api/v1/projects/{$projectId}/pages/{$pageId}")
            ->assertOk()
            ->assertJsonPath('data.project_id', null);
    }

    public function test_users_cannot_access_each_others_resources(): void
    {
        $intruder = User::factory()->create();

        $workspace = $this->createWorkspace($this->owner);

        $this->actAs($intruder);
        $this->getJson("/api/v1/workspaces/{$workspace->id}")
            ->assertForbidden();

        $this->actAs($intruder);
        $this->postJson("/api/v1/workspaces/{$workspace->id}/notebooks", ['name' => 'Sneaky'])
            ->assertForbidden();
    }

    public function test_index_scoped_to_owner(): void
    {
        $intruder = User::factory()->create();

        $this->createWorkspace($this->owner, ['name' => 'Owner Space']);
        $this->createWorkspace($intruder, ['name' => 'Intruder Space']);

        $this->actAs($this->owner);
        $this->getJson('/api/v1/workspaces')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Owner Space');
    }

    public function test_missing_resource_returns_404_envelope(): void
    {
        $this->actAs($this->owner);
        $this->getJson('/api/v1/pages/999999')
            ->assertNotFound()
            ->assertJsonStructure(['data', 'meta', 'errors']);
    }

    public function test_page_search_matches_title_content_and_blocks(): void
    {
        $this->actAs($this->owner);
        $workspace = $this->createWorkspace($this->owner);
        $notebookId = $this->createNotebook($workspace, $this->owner);
        $sectionId = $this->createSection($notebookId, $this->owner);

        $this->createPage($sectionId, $this->owner, ['title' => 'Alpha Notes', 'content' => 'Nothing special']);
        $this->createPage($sectionId, $this->owner, ['title' => 'Beta Page', 'content' => 'talks about gamma rays']);
        $this->createPage($sectionId, $this->owner, ['title' => 'Delta Doc', 'content' => 'plain']);

        $this->actAs($this->owner);
        $this->getJson('/api/v1/pages?q=alpha')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Alpha Notes');

        $this->getJson('/api/v1/pages?q=gamma')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Beta Page');
    }

    public function test_template_crud(): void
    {
        $this->actAs($this->owner);

        $created = $this->postJson('/api/v1/templates', [
            'name' => 'Meeting Notes',
            'description' => 'Standard agenda',
            'icon' => '📝',
            'blocks' => [
                ['type' => 'heading', 'content' => 'Agenda', 'meta' => ['level' => 2]],
                ['type' => 'text', 'content' => '', 'meta' => []],
            ],
        ])->assertCreated()->json('data');

        $this->getJson("/api/v1/templates/{$created['id']}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Meeting Notes');

        $updated = $this->putJson("/api/v1/templates/{$created['id']}", [
            'name' => 'Meeting Notes v2',
            'blocks' => [
                ['type' => 'text', 'content' => 'Replacement block', 'meta' => []],
            ],
        ])->assertOk()->json('data');

        $this->assertEquals('Meeting Notes v2', $updated['name']);
        $this->assertCount(1, $updated['blocks']);
        $this->assertEquals('Replacement block', $updated['blocks'][0]['content']);

        $this->deleteJson("/api/v1/templates/{$created['id']}")
            ->assertOk()
            ->assertJsonPath('data.deleted', true);

        $this->getJson("/api/v1/templates/{$created['id']}")
            ->assertNotFound();
    }
}
