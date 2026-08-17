<?php

namespace App\Domains\DecisionJournal\Http\Controllers;

use App\Domains\DecisionJournal\Http\Resources\DecisionResource;
use App\Domains\DecisionJournal\Models\Decision;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class DecisionController extends ApiController
{
    public function index(Request $request)
    {
        $items = Decision::forUser($request->user()->id)->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))->when($request->input('tag'), fn ($q, $t) => $q->whereJsonContains('tags', $t))->orderByDesc('created_at')->paginate($request->integer('per_page', 25));
        return $this->respondPaginated($items, DecisionResource::class);
    }

    public function store(Request $request)
    {
        $decision = Decision::create(array_merge(['user_id' => $request->user()->id], $this->validateDecision($request)));
        return $this->respondCreated(DecisionResource::make($decision)->toArray($request));
    }

    public function show(Request $request, Decision $decision)
    {
        $this->authorizeOwner($request, $decision);
        return $this->respondSuccess(DecisionResource::make($decision)->toArray($request));
    }

    public function update(Request $request, Decision $decision)
    {
        $this->authorizeOwner($request, $decision);
        $data = $this->validateDecision($request, true);
        if (($data['status'] ?? null) === 'decided' && !$decision->decided_at) {
            $data['decided_at'] = now()->toDateString();
        }
        $decision->update($data);
        return $this->respondSuccess(DecisionResource::make($decision)->toArray($request));
    }

    public function destroy(Request $request, Decision $decision)
    {
        $this->authorizeOwner($request, $decision);
        $decision->delete();
        return $this->respondNoContent();
    }

    public function dueReviews(Request $request)
    {
        $items = Decision::forUser($request->user()->id)->whereNotNull('review_at')->whereDate('review_at', '<=', now())->whereIn('status', ['decided', 'open'])->orderBy('review_at')->get();
        return $this->respondSuccess(DecisionResource::collection($items));
    }

    public function stats(Request $request)
    {
        $items = Decision::forUser($request->user()->id)->get();
        return $this->respondSuccess(['total' => $items->count(), 'by_status' => $items->groupBy('status')->map->count(), 'average_confidence' => $items->whereNotNull('confidence')->isEmpty() ? null : round($items->whereNotNull('confidence')->avg('confidence'), 2), 'due_reviews' => $items->filter(fn (Decision $d) => $d->review_at && $d->review_at->lte(now()) && in_array($d->status, ['open', 'decided'], true))->count()]);
    }

    private function validateDecision(Request $request, bool $partial = false): array
    {
        return $request->validate(['title' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'], 'context' => ['nullable', 'string'], 'options' => ['nullable', 'array'], 'decision' => ['nullable', 'string'], 'rationale' => ['nullable', 'string'], 'confidence' => ['nullable', 'integer', 'between:1,10'], 'status' => ['sometimes', 'in:open,decided,revisited'], 'decided_at' => ['nullable', 'date'], 'review_at' => ['nullable', 'date'], 'outcome' => ['nullable', 'string'], 'tags' => ['nullable', 'array'], 'tags.*' => ['string', 'max:64'], 'metadata' => ['nullable', 'array']]);
    }

    private function authorizeOwner(Request $request, Decision $decision): void
    {
        if ($decision->user_id !== $request->user()->id) {
            abort(404);
        }
    }
}
