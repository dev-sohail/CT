<?php

namespace App\Domains\SecondBrainPersonalWiki\Http\Controllers;

use App\Domains\SecondBrainPersonalWiki\Http\Requests\PageVersionRequest;
use App\Domains\SecondBrainPersonalWiki\Http\Resources\PageResource;
use App\Domains\SecondBrainPersonalWiki\Http\Resources\PageVersionResource;
use App\Domains\SecondBrainPersonalWiki\Models\Page;
use App\Domains\SecondBrainPersonalWiki\Models\PageVersion;
use App\Domains\SecondBrainPersonalWiki\Services\WorkspaceScope;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class PageVersionController extends ApiController
{
    public function __construct(private readonly WorkspaceScope $scope) {}

    public function index(Request $request, Page $page)
    {
        $this->scope->assertOwns($page, ['section', 'notebook', 'workspace'], $request->user()->id);

        return $this->respondSuccess(PageVersionResource::collection($page->versions()->limit(50)->get()));
    }

    public function store(PageVersionRequest $request, Page $page)
    {
        $this->scope->assertOwns($page, ['section', 'notebook', 'workspace'], $request->user()->id);

        $version = $page->versions()->create([
            'title' => $request->validated('title') ?? $page->title,
            'content' => $request->validated('content') ?? $page->content,
            'blocks' => $request->validated('blocks') ?? $page->blocks()->get()->toArray(),
            'note' => $request->validated('note'),
        ]);

        return $this->respondCreated(PageVersionResource::make($version));
    }

    public function restore(Request $request, PageVersion $version)
    {
        $page = $version->page;
        $this->scope->assertOwns($page, ['section', 'notebook', 'workspace'], $request->user()->id);

        $page->title = $version->title ?? $page->title;
        $page->content = $version->content ?? $page->content;
        $page->save();

        if (is_array($version->blocks)) {
            $page->blocks()->delete();
            $sortOrder = 0;
            foreach ($version->blocks as $blockData) {
                $page->blocks()->create([
                    'type' => $blockData['type'] ?? 'text',
                    'content' => $blockData['content'] ?? null,
                    'meta' => $blockData['meta'] ?? null,
                    'sort_order' => $sortOrder++,
                ]);
            }
        }

        return $this->respondSuccess(PageResource::make($page->fresh('blocks')));
    }
}
