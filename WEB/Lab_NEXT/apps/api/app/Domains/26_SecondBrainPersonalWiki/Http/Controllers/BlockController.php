<?php

namespace App\Domains\SecondBrainPersonalWiki\Http\Controllers;

use App\Domains\SecondBrainPersonalWiki\Http\Requests\BlockSyncRequest;
use App\Domains\SecondBrainPersonalWiki\Http\Resources\BlockResource;
use App\Domains\SecondBrainPersonalWiki\Models\Block;
use App\Domains\SecondBrainPersonalWiki\Models\Page;
use App\Domains\SecondBrainPersonalWiki\Services\WorkspaceScope;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class BlockController extends ApiController
{
    public function __construct(private readonly WorkspaceScope $scope) {}

    public function index(Request $request, Page $page)
    {
        $this->scope->assertOwns($page, ['section', 'notebook', 'workspace'], $request->user()->id);

        return $this->respondSuccess(BlockResource::collection($page->blocks()->get()));
    }

    public function show(Request $request, Block $block)
    {
        $this->scope->assertOwns($block, ['page', 'section', 'notebook', 'workspace'], $request->user()->id);

        return $this->respondSuccess(BlockResource::make($block));
    }

    public function sync(BlockSyncRequest $request, Page $page)
    {
        $this->scope->assertOwns($page, ['section', 'notebook', 'workspace'], $request->user()->id);

        $submitted = $request->validated('blocks');
        $submittedIds = [];
        $created = [];

        foreach ($submitted as $i => $blockData) {
            $sortOrder = $blockData['sort_order'] ?? $i;
            $meta = $blockData['meta'] ?? null;

            if (! empty($blockData['id'])) {
                $block = Block::where('page_id', $page->id)
                    ->where('id', $blockData['id'])
                    ->first();

                if ($block) {
                    $block->update([
                        'type' => $blockData['type'],
                        'content' => $blockData['content'] ?? null,
                        'meta' => $meta,
                        'parent_id' => $blockData['parent_id'] ?? null,
                        'sort_order' => $sortOrder,
                    ]);
                    $submittedIds[] = $block->id;

                    continue;
                }
            }

            $block = $page->blocks()->create([
                'type' => $blockData['type'],
                'content' => $blockData['content'] ?? null,
                'meta' => $meta,
                'parent_id' => $blockData['parent_id'] ?? null,
                'sort_order' => $sortOrder,
            ]);
            $submittedIds[] = $block->id;
            $created[] = $block;
        }

        Block::where('page_id', $page->id)
            ->whereNotIn('id', $submittedIds)
            ->delete();

        $page->touch();

        return $this->respondSuccess(
            BlockResource::collection($page->blocks()->get()),
            [
                'created' => collect($created)->pluck('id'),
                'updated_at' => $page->fresh()->updated_at,
            ]
        );
    }

    public function update(Request $request, Block $block)
    {
        $this->scope->assertOwns($block, ['page', 'section', 'notebook', 'workspace'], $request->user()->id);

        $validated = $request->validate([
            'type' => 'sometimes|string|max:100',
            'content' => 'nullable|string',
            'meta' => 'nullable|array',
            'parent_id' => 'nullable|integer',
            'sort_order' => 'nullable|integer',
        ]);

        $block->update($validated);

        return $this->respondSuccess(BlockResource::make($block));
    }

    public function destroy(Request $request, Block $block)
    {
        $this->scope->assertOwns($block, ['page', 'section', 'notebook', 'workspace'], $request->user()->id);

        $block->delete();

        return $this->respondNoContent();
    }
}
