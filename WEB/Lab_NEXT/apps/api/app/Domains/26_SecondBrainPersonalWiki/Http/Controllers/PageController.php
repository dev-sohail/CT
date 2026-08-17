<?php

namespace App\Domains\SecondBrainPersonalWiki\Http\Controllers;

use App\Domains\SecondBrainPersonalWiki\Http\Requests\PageRequest;
use App\Domains\SecondBrainPersonalWiki\Http\Resources\PageResource;
use App\Domains\SecondBrainPersonalWiki\Models\Block;
use App\Domains\SecondBrainPersonalWiki\Models\Page;
use App\Domains\SecondBrainPersonalWiki\Models\Section;
use App\Domains\SecondBrainPersonalWiki\Models\Tag;
use App\Domains\SecondBrainPersonalWiki\Services\WorkspaceScope;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class PageController extends ApiController
{
    public function __construct(private readonly WorkspaceScope $scope) {}

    public function index(Request $request, ?Section $section = null)
    {
        $query = Page::query()
            ->with('tags')
            ->whereHas('section.notebook.workspace', fn ($q) => $q->where('user_id', $request->user()->id));

        if ($request->has('project_id')) {
            $query->where('project_id', $request->integer('project_id'));
        }
        if ($request->boolean('favorites')) {
            $query->where('is_favorite', true);
        }
        if ($section?->exists) {
            $query->where('section_id', $section->id);
        }
        if ($request->has('q')) {
            $q = $request->string('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', '%'.$q.'%')
                    ->orWhere('content', 'like', '%'.$q.'%')
                    ->orWhereHas('blocks', fn ($b) => $b->where('content', 'like', '%'.$q.'%'));
            });
        }

        return $this->respondSuccess(PageResource::collection($query->orderBy('title')->get()));
    }

    public function store(PageRequest $request, Section $section)
    {
        $this->scope->assertOwns($section, ['notebook', 'workspace'], $request->user()->id);

        $page = $section->pages()->create($request->validated());
        $this->syncTags($request, $page);

        return $this->respondCreated(PageResource::make($page->fresh('blocks', 'tags')));
    }

    public function show(Request $request, Page $page)
    {
        $this->scope->assertOwns($page, ['section', 'notebook', 'workspace'], $request->user()->id);

        $page->load('section', 'blocks', 'tags', 'project');

        return $this->respondSuccess(PageResource::make($page));
    }

    public function update(PageRequest $request, Page $page)
    {
        $this->scope->assertOwns($page, ['section', 'notebook', 'workspace'], $request->user()->id);

        $page->update($request->validated());
        $this->syncTags($request, $page);

        return $this->respondSuccess(PageResource::make($page->fresh('blocks', 'tags')));
    }

    public function destroy(Request $request, Page $page)
    {
        $this->scope->assertOwns($page, ['section', 'notebook', 'workspace'], $request->user()->id);

        $page->delete();

        return $this->respondNoContent();
    }

    public function toggleFavorite(Request $request, Page $page)
    {
        $this->scope->assertOwns($page, ['section', 'notebook', 'workspace'], $request->user()->id);

        $page->is_favorite = ! $page->is_favorite;
        $page->pinned_at = $page->is_favorite ? now() : null;
        $page->save();

        return $this->respondSuccess(PageResource::make($page->fresh()));
    }

    public function trash(Request $request)
    {
        $pages = Page::onlyTrashed()
            ->whereHas('section.notebook.workspace', fn ($q) => $q->where('user_id', $request->user()->id))
            ->orderByDesc('deleted_at')
            ->get();

        return $this->respondSuccess(PageResource::collection($pages));
    }

    public function restore(Request $request, Page $page)
    {
        $this->scope->assertOwns($page, ['section', 'notebook', 'workspace'], $request->user()->id);

        $page->restore();

        return $this->respondSuccess(PageResource::make($page->fresh()));
    }

    public function forceDestroy(Request $request, Page $page)
    {
        $this->scope->assertOwns($page, ['section', 'notebook', 'workspace'], $request->user()->id);

        $page->forceDelete();

        return $this->respondNoContent();
    }

    public function backlinks(Request $request, Page $page)
    {
        $this->scope->assertOwns($page, ['section', 'notebook', 'workspace'], $request->user()->id);

        $pattern = '/\\[\\['.preg_quote($page->title, '/').'\\]\\]/iu';

        $matches = Block::where('page_id', '!=', $page->id)
            ->whereNotNull('content')
            ->get(['id', 'page_id', 'content'])
            ->filter(fn ($block) => preg_match($pattern, $block->content))
            ->pluck('page_id')
            ->unique();

        $sources = Page::whereIn('id', $matches)->get(['id', 'title', 'icon'])->map(function ($p) use ($page) {
            $snippet = Block::where('page_id', $p->id)
                ->whereNotNull('content')
                ->whereRaw('content REGEXP ?', ['\\['.preg_quote($page->title, '/').'\\]'])
                ->first()?->content ?? '';

            $snippet = preg_replace('/\\[\\['.preg_quote($page->title, '/').'\\]\\]/iu', $page->title, $snippet);

            return [
                'id' => $p->id,
                'title' => $p->title,
                'icon' => $p->icon,
                'snippet' => mb_substr(strip_tags($snippet), 0, 160),
            ];
        })->values();

        return $this->respondSuccess($sources);
    }

    protected function syncTags(Request $request, Page $page)
    {
        if (! $request->filled('tags') && ! $request->has('tags')) {
            return;
        }

        $tagIds = [];
        foreach ((array) $request->input('tags', []) as $tagName) {
            $tagName = trim((string) $tagName);
            if ($tagName === '') {
                continue;
            }
            $tag = Tag::firstOrCreate(['name' => $tagName]);
            $tagIds[] = $tag->id;
        }

        $page->tags()->sync($tagIds);
    }
}
