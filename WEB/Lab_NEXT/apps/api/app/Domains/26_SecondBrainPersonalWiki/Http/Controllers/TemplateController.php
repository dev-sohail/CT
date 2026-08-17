<?php

namespace App\Domains\SecondBrainPersonalWiki\Http\Controllers;

use App\Domains\SecondBrainPersonalWiki\Http\Requests\TemplateRequest;
use App\Domains\SecondBrainPersonalWiki\Http\Resources\PageResource;
use App\Domains\SecondBrainPersonalWiki\Http\Resources\TemplateResource;
use App\Domains\SecondBrainPersonalWiki\Models\Section;
use App\Domains\SecondBrainPersonalWiki\Models\Template;
use App\Domains\SecondBrainPersonalWiki\Services\WorkspaceScope;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TemplateController extends ApiController
{
    public function __construct(private readonly WorkspaceScope $scope) {}

    public function index(Request $request)
    {
        return $this->respondSuccess(TemplateResource::collection(Template::orderBy('name')->get()));
    }

    public function store(TemplateRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        return $this->respondCreated(TemplateResource::make(Template::create($data)));
    }

    public function use(Request $request, Template $template, Section $section)
    {
        $this->scope->assertOwns($section, ['notebook', 'workspace'], $request->user()->id);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $page = $section->pages()->create([
            'title' => $validated['title'] ?: $template->name,
            'content' => null,
            'icon' => $template->icon,
            'type' => 'page',
        ]);

        $blocks = $template->blocks ?? [];
        $sortOrder = 0;
        foreach ($blocks as $blockData) {
            $page->blocks()->create([
                'type' => $blockData['type'] ?? 'text',
                'content' => $blockData['content'] ?? null,
                'meta' => $blockData['meta'] ?? null,
                'sort_order' => $sortOrder++,
            ]);
        }

        return $this->respondCreated(
            PageResource::make($page->fresh('blocks'))
        );
    }

    public function show(Request $request, Template $template)
    {
        return $this->respondSuccess(TemplateResource::make($template));
    }

    public function update(TemplateRequest $request, Template $template)
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        if (array_key_exists('blocks', $data)) {
            $data['blocks'] = collect($data['blocks'])->values()
                ->map(fn ($block, $sortOrder) => [
                    'type' => $block['type'] ?? 'text',
                    'content' => $block['content'] ?? null,
                    'meta' => $block['meta'] ?? null,
                    'sort_order' => $sortOrder,
                ])
                ->all();
        }

        $template->update($data);

        return $this->respondSuccess(TemplateResource::make($template->fresh()));
    }

    public function destroy(Request $request, Template $template)
    {
        $template->delete();

        return $this->respondSuccess(['deleted' => true]);
    }
}
