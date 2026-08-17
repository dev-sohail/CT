<?php

namespace App\Domains\SecondBrainPersonalWiki\Http\Controllers;

use App\Domains\SecondBrainPersonalWiki\Http\Requests\QuestionRequest;
use App\Domains\SecondBrainPersonalWiki\Http\Resources\QuestionResource;
use App\Domains\SecondBrainPersonalWiki\Models\Question;
use App\Domains\SecondBrainPersonalWiki\Models\Workspace;
use App\Domains\SecondBrainPersonalWiki\Services\WorkspaceScope;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class QuestionController extends ApiController
{
    public function __construct(private readonly WorkspaceScope $scope) {}

    public function index(Request $request, ?Workspace $workspace = null)
    {
        $query = Question::query()
            ->whereHas('workspace', fn ($q) => $q->where('user_id', $request->user()->id));

        if ($workspace?->exists) {
            $query->where('workspace_id', $workspace->id);
        }
        if ($request->has('status')) {
            $query->where('status', $request->string('status'));
        }

        return $this->respondSuccess(QuestionResource::collection($query->orderByDesc('updated_at')->get()));
    }

    public function store(QuestionRequest $request, Workspace $workspace)
    {
        $this->scope->assertOwns($workspace, [], $request->user()->id);

        return $this->respondCreated(QuestionResource::make($workspace->questions()->create($request->validated())));
    }

    public function show(Request $request, Question $question)
    {
        $this->scope->assertOwns($question, ['workspace'], $request->user()->id);

        return $this->respondSuccess(QuestionResource::make($question));
    }

    public function update(QuestionRequest $request, Question $question)
    {
        $this->scope->assertOwns($question, ['workspace'], $request->user()->id);

        $question->update($request->validated());

        return $this->respondSuccess(QuestionResource::make($question->fresh()));
    }

    public function destroy(Request $request, Question $question)
    {
        $this->scope->assertOwns($question, ['workspace'], $request->user()->id);

        $question->delete();

        return $this->respondNoContent();
    }
}
