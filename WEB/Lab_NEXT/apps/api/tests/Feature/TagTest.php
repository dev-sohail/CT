<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\SecondBrainPersonalWiki\Models\Notebook;
use App\Domains\SecondBrainPersonalWiki\Models\Page;
use App\Domains\SecondBrainPersonalWiki\Models\Section;
use App\Domains\SecondBrainPersonalWiki\Models\Workspace;
use App\Domains\TaggingAndCategorizationEngine\Models\Tag;
use App\Domains\TaggingAndCategorizationEngine\Services\TagService;
use Laravel\Sanctum\Sanctum;

class TagTest extends FeatureTestCase
{
    private function makePage(User $user, string $title): Page
    {
        $workspace = Workspace::create(['user_id' => $user->id, 'name' => 'WS', 'is_default' => false]);
        $notebook = Notebook::create(['workspace_id' => $workspace->id, 'name' => 'NB']);
        $section = Section::create(['notebook_id' => $notebook->id, 'name' => 'SC']);

        return Page::create(['section_id' => $section->id, 'title' => $title, 'content' => 'content']);
    }

    public function test_tag_crud_lifecycle(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/tags', ['name' => 'Finance', 'color' => '#ff0000'])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'finance');

        $tag = Tag::first();
        $this->putJson("/api/v1/tags/{$tag->id}", ['name' => 'Money'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Money');

        $this->getJson('/api/v1/tags')->assertOk()->assertJsonCount(1, 'data');

        $this->deleteJson("/api/v1/tags/{$tag->id}")->assertNoContent();
        $this->assertDatabaseCount('tags', 0);
    }

    public function test_attach_and_detach_via_service(): void
    {
        $user = User::factory()->create();
        $page = $this->makePage($user, 'Quarterly report');
        $service = app(TagService::class);

        $service->attach($user->id, $page, ['finance', 'Important']);

        $this->assertDatabaseHas('taggables', [
            'tag_id' => Tag::where('slug', 'finance')->first()->id,
            'taggable_type' => Page::class,
            'taggable_id' => $page->id,
        ]);

        $this->assertSame(2, $service->tagsFor($page)->count());

        $service->detach($user->id, $page, ['finance']);
        $this->assertSame(1, $service->tagsFor($page)->count());

        $service->sync($user->id, $page, ['report']);
        $this->assertSame(['report'], $service->tagsFor($page)->pluck('slug')->all());
    }

    public function test_attach_and_detach_via_api(): void
    {
        $user = User::factory()->create();
        $page = $this->makePage($user, 'Tax docs');
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/tags/attach', [
            'taggable_type' => Page::class,
            'taggable_id' => $page->id,
            'names' => ['tax', '2026'],
        ])->assertOk()->assertJsonCount(2, 'data');

        $this->assertDatabaseCount('taggables', 2);

        $this->postJson('/api/v1/tags/detach', [
            'taggable_type' => Page::class,
            'taggable_id' => $page->id,
            'names' => ['tax'],
        ])->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_user_tags_include_counts(): void
    {
        $user = User::factory()->create();
        $page = $this->makePage($user, 'Page one');
        $page2 = $this->makePage($user, 'Page two');

        $service = app(TagService::class);
        $service->attach($user->id, $page, ['shared']);
        $service->attach($user->id, $page2, ['shared']);

        Sanctum::actingAs($user);
        $this->getJson('/api/v1/tags')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.count', 2);
    }

    public function test_attach_enforces_owner_boundary(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $page = $this->makePage($alice, 'Alice private');
        Sanctum::actingAs($bob);

        $this->postJson('/api/v1/tags/attach', [
            'taggable_type' => Page::class,
            'taggable_id' => $page->id,
            'names' => ['sneaky'],
        ])->assertNotFound();

        $this->assertDatabaseCount('taggables', 0);
    }

    public function test_tags_are_scoped_to_owner(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        app(TagService::class)->attach($alice->id, $this->makePage($alice, 'A'), ['alice-tag']);

        Sanctum::actingAs($bob);
        $this->getJson('/api/v1/tags')->assertOk()->assertJsonCount(0, 'data');
    }
}
