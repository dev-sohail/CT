<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\SecondBrainPersonalWiki\Models\Notebook;
use App\Domains\SecondBrainPersonalWiki\Models\Page;
use App\Domains\SecondBrainPersonalWiki\Models\Section;
use App\Domains\SecondBrainPersonalWiki\Models\Workspace;
use App\Domains\UnifiedSearchEngine\Contracts\Searchable;
use App\Domains\UnifiedSearchEngine\Models\SearchEntry;
use App\Domains\UnifiedSearchEngine\Services\SearchService;
use Laravel\Sanctum\Sanctum;

class SearchTest extends FeatureTestCase
{
    private function makePage(User $user, string $title, string $content, ?string $type = null): Page
    {
        $workspace = Workspace::create(['user_id' => $user->id, 'name' => 'WS', 'is_default' => false]);
        $notebook = Notebook::create(['workspace_id' => $workspace->id, 'name' => 'NB']);
        $section = Section::create(['notebook_id' => $notebook->id, 'name' => 'SC']);

        $attrs = ['section_id' => $section->id, 'title' => $title, 'content' => $content];
        if ($type) {
            $attrs['type'] = $type;
        }

        return Page::create($attrs);
    }

    public function test_page_save_auto_indexes(): void
    {
        $user = User::factory()->create();
        $page = $this->makePage($user, 'React hooks guide', 'Everything about useEffect');

        $this->assertDatabaseHas('search_index', [
            'user_id' => $user->id,
            'searchable_type' => Page::class,
            'searchable_id' => $page->id,
            'title' => 'React hooks guide',
        ]);
    }

    public function test_search_ranks_title_matches_first(): void
    {
        $user = User::factory()->create();
        $this->makePage($user, 'Budget planning', 'A note about monthly planning');
        $this->makePage($user, 'Monthly review', 'Deep dive into budget allocation');

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/search?q=budget')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $titles = collect($response->json('data'))->pluck('title');
        $this->assertSame(['Budget planning', 'Monthly review'], $titles->all());
    }

    public function test_search_respects_owner_boundary(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $this->makePage($alice, 'Alice secret', 'private plans');
        $this->makePage($bob, 'Bob public', 'shared plans');

        Sanctum::actingAs($alice);
        $response = $this->getJson('/api/v1/search?q=plans')->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame('Alice secret', $response->json('data.0.title'));
    }

    public function test_search_requires_query(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/search')->assertUnprocessable();
        $this->getJson('/api/v1/search?q=x')->assertUnprocessable();
    }

    public function test_soft_delete_removes_from_index(): void
    {
        $user = User::factory()->create();
        $page = $this->makePage($user, 'Temp page', 'to be removed');

        $page->delete();

        $this->assertDatabaseMissing('search_index', [
            'searchable_type' => Page::class,
            'searchable_id' => $page->id,
        ]);
    }

    public function test_search_service_rebuild_and_type_filter(): void
    {
        $user = User::factory()->create();
        $page = $this->makePage($user, 'Service indexed', 'rebuild me');

        app(SearchService::class)->rebuild($page);
        $this->assertSame(1, SearchEntry::where('searchable_type', Page::class)->count());

        Sanctum::actingAs($user);
        $this->getJson('/api/v1/search?q=service&type='.Page::class)
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/v1/search?q=service&type=DoesNotExist')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_contract_owner_chain(): void
    {
        $user = User::factory()->create();
        $page = $this->makePage($user, 'Chain', 'content');
        $this->assertInstanceOf(Searchable::class, $page);
        $this->assertSame($user->id, $page->searchOwnerId());
    }
}
