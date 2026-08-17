<?php

namespace App\Domains\SecondBrainPersonalWiki\Http\Controllers;

use App\Domains\SecondBrainPersonalWiki\Http\Requests\ReviewRequest;
use App\Domains\SecondBrainPersonalWiki\Http\Resources\ReviewResource;
use App\Domains\SecondBrainPersonalWiki\Models\Review;
use App\Domains\SecondBrainPersonalWiki\Models\Workspace;
use App\Domains\SecondBrainPersonalWiki\Services\WorkspaceScope;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class ReviewController extends ApiController
{
    public const INTERVALS = [
        'daily' => 1,
        'weekly' => 7,
        'monthly' => 30,
        'technical' => 14,
    ];

    public function __construct(private readonly WorkspaceScope $scope) {}

    public function index(Request $request, ?Workspace $workspace = null)
    {
        $query = Review::query()
            ->with('page')
            ->whereHas('workspace', fn ($q) => $q->where('user_id', $request->user()->id));

        if ($workspace?->exists) {
            $query->where('workspace_id', $workspace->id);
        }
        if ($request->has('status')) {
            $query->where('status', $request->string('status'));
        }

        return $this->respondSuccess(ReviewResource::collection($query->orderBy('next_review_at')->orderByDesc('updated_at')->get()));
    }

    public function store(ReviewRequest $request, Workspace $workspace)
    {
        $this->scope->assertOwns($workspace, [], $request->user()->id);

        return $this->respondCreated(ReviewResource::make($workspace->reviews()->create($request->validated())));
    }

    public function show(Request $request, Review $review)
    {
        $this->scope->assertOwns($review, ['workspace'], $request->user()->id);

        return $this->respondSuccess(ReviewResource::make($review->load('page')));
    }

    public function update(ReviewRequest $request, Review $review)
    {
        $this->scope->assertOwns($review, ['workspace'], $request->user()->id);

        $review->update($request->validated());

        return $this->respondSuccess(ReviewResource::make($review->fresh('page')));
    }

    public function destroy(Request $request, Review $review)
    {
        $this->scope->assertOwns($review, ['workspace'], $request->user()->id);

        $review->delete();

        return $this->respondNoContent();
    }

    public function markDone(Request $request, Review $review)
    {
        $this->scope->assertOwns($review, ['workspace'], $request->user()->id);

        $now = now();
        $review->status = 'done';
        $review->last_reviewed_at = $now;
        $review->next_review_at = $now->copy()->addDays(self::INTERVALS[$review->type] ?? 7);
        $review->scheduled_for = $review->next_review_at->toDateString();
        $review->save();

        return $this->respondSuccess(ReviewResource::make($review->fresh('page')));
    }
}
