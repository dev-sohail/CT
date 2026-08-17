<?php

namespace App\Domains\SecondBrainPersonalWiki\Http\Controllers;

use App\Domains\SecondBrainPersonalWiki\Http\Requests\SectionRequest;
use App\Domains\SecondBrainPersonalWiki\Http\Resources\SectionResource;
use App\Domains\SecondBrainPersonalWiki\Models\Notebook;
use App\Domains\SecondBrainPersonalWiki\Models\Section;
use App\Domains\SecondBrainPersonalWiki\Services\WorkspaceScope;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class SectionController extends ApiController
{
    public function __construct(private readonly WorkspaceScope $scope) {}

    public function index(Request $request, ?Notebook $notebook = null)
    {
        $query = Section::query()
            ->whereHas('notebook.workspace', fn ($q) => $q->where('user_id', $request->user()->id))
            ->with('pages');

        if ($notebook?->exists) {
            $query->where('notebook_id', $notebook->id);
        }

        return $this->respondSuccess(SectionResource::collection($query->get()));
    }

    public function store(SectionRequest $request, Notebook $notebook)
    {
        $this->scope->assertOwns($notebook, ['workspace'], $request->user()->id);

        $section = $notebook->sections()->create($request->validated());

        return $this->respondCreated(SectionResource::make($section->fresh('pages')));
    }

    public function show(Request $request, Section $section)
    {
        $this->scope->assertOwns($section, ['notebook', 'workspace'], $request->user()->id);

        $section->load('pages');

        return $this->respondSuccess(SectionResource::make($section));
    }

    public function update(SectionRequest $request, Section $section)
    {
        $this->scope->assertOwns($section, ['notebook', 'workspace'], $request->user()->id);

        $section->update($request->validated());

        return $this->respondSuccess(SectionResource::make($section->fresh('pages')));
    }

    public function destroy(Request $request, Section $section)
    {
        $this->scope->assertOwns($section, ['notebook', 'workspace'], $request->user()->id);

        $section->delete();

        return $this->respondNoContent();
    }
}
