<?php

namespace App\Domains\FlashcardEngineSM2\Http\Controllers;

use App\Domains\FlashcardEngineSM2\Http\Resources\DeckResource;
use App\Domains\FlashcardEngineSM2\Http\Resources\FlashcardResource;
use App\Domains\FlashcardEngineSM2\Models\Deck;
use App\Domains\FlashcardEngineSM2\Models\Flashcard;
use App\Domains\FlashcardEngineSM2\Models\FlashcardReview;
use App\Domains\FlashcardEngineSM2\Services\Sm2Algorithm;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class FlashcardController extends ApiController
{
    public function __construct(private Sm2Algorithm $sm2)
    {
    }

    // ------------------------------------------------------------------
    // Decks
    // ------------------------------------------------------------------

    public function decks(Request $request)
    {
        $decks = Deck::forUser($request->user()->id)
            ->with('flashcards')
            ->orderBy('title')
            ->get();

        return $this->respondSuccess(DeckResource::collection($decks));
    }

    public function storeDeck(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $deck = Deck::create(array_merge(['user_id' => $request->user()->id], $validated));

        return $this->respondCreated(DeckResource::make($deck));
    }

    public function showDeck(Request $request, Deck $deck)
    {
        if ($deck->user_id !== $request->user()->id) {
            return $this->respondError('Deck not found.', 404);
        }
        return $this->respondSuccess(DeckResource::make($deck->load('flashcards')));
    }

    public function updateDeck(Request $request, Deck $deck)
    {
        if ($deck->user_id !== $request->user()->id) {
            return $this->respondError('Deck not found.', 404);
        }
        $deck->update($request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]));
        return $this->respondSuccess(DeckResource::make($deck));
    }

    public function destroyDeck(Request $request, Deck $deck)
    {
        if ($deck->user_id !== $request->user()->id) {
            return $this->respondError('Deck not found.', 404);
        }
        $deck->delete();
        return $this->respondNoContent();
    }

    // ------------------------------------------------------------------
    // Flashcards
    // ------------------------------------------------------------------

    public function index(Request $request, ?Deck $deck = null)
    {
        $query = Flashcard::forUser($request->user()->id)
            ->when($deck, fn ($q) => $q->where('deck_id', $deck->id))
            ->when($request->boolean('due_only'), fn ($q) => $q->due())
            ->orderBy('due_at')
            ->get();

        return $this->respondSuccess(FlashcardResource::collection($query));
    }

    public function store(Request $request, ?Deck $deck = null)
    {
        $validated = $request->validate([
            'deck_id' => ['nullable', 'exists:decks,id'],
            'question' => ['required', 'string'],
            'answer' => ['required', 'string'],
        ]);

        $deckId = $deck?->id ?? $validated['deck_id'] ?? null;
        if (!$deckId) {
            return $this->respondError('Deck is required.', 422);
        }
        $deck = Deck::find($deckId);
        if (!$deck || $deck->user_id !== $request->user()->id) {
            return $this->respondError('Deck not found.', 404);
        }

        $flashcard = Flashcard::create([
            'deck_id' => $deckId,
            'user_id' => $request->user()->id,
            'question' => $validated['question'],
            'answer' => $validated['answer'],
        ]);

        return $this->respondCreated(FlashcardResource::make($flashcard));
    }

    public function show(Request $request, Flashcard $flashcard)
    {
        if ($flashcard->user_id !== $request->user()->id) {
            return $this->respondError('Flashcard not found.', 404);
        }
        return $this->respondSuccess(FlashcardResource::make($flashcard));
    }

    public function update(Request $request, Flashcard $flashcard)
    {
        if ($flashcard->user_id !== $request->user()->id) {
            return $this->respondError('Flashcard not found.', 404);
        }
        $flashcard->update($request->validate([
            'question' => ['sometimes', 'string'],
            'answer' => ['sometimes', 'string'],
        ]));
        return $this->respondSuccess(FlashcardResource::make($flashcard));
    }

    public function destroy(Request $request, Flashcard $flashcard)
    {
        if ($flashcard->user_id !== $request->user()->id) {
            return $this->respondError('Flashcard not found.', 404);
        }
        $flashcard->delete();
        return $this->respondNoContent();
    }

    /**
     * Submit a recall quality (0-5); SM-2 updates the scheduler state.
     */
    public function review(Request $request, Flashcard $flashcard)
    {
        if ($flashcard->user_id !== $request->user()->id) {
            return $this->respondError('Flashcard not found.', 404);
        }

        $validated = $request->validate(['quality' => ['required', 'integer', 'between:0,5']]);
        $quality = (int) $validated['quality'];

        $next = $this->sm2->schedule($quality, $flashcard->ease_factor, $flashcard->interval_days, $flashcard->repetitions);

        FlashcardReview::create(['flashcard_id' => $flashcard->id, 'quality' => $quality]);

        $flashcard->update([
            'ease_factor' => $next['ease_factor'],
            'interval_days' => $next['interval_days'],
            'repetitions' => $next['repetitions'],
            'due_at' => now()->addDays($next['interval_days']),
            'last_reviewed_at' => now(),
        ]);

        return $this->respondSuccess([
            'flashcard' => FlashcardResource::make($flashcard),
            'next' => $next,
        ]);
    }

    public function due(Request $request, ?Deck $deck = null)
    {
        $cards = Flashcard::forUser($request->user()->id)
            ->when($deck, fn ($q) => $q->where('deck_id', $deck->id))
            ->due()
            ->orderBy('due_at')
            ->limit((int) $request->input('limit', 20))
            ->get();

        return $this->respondSuccess(FlashcardResource::collection($cards));
    }

    public function stats(Request $request)
    {
        $cards = Flashcard::forUser($request->user()->id)->get();
        $reviews = FlashcardReview::whereIn('flashcard_id', $cards->pluck('id'))->get();

        return $this->respondSuccess([
            'total_cards' => $cards->count(),
            'due_now' => $cards->filter(fn ($c) => $c->due_at === null || $c->due_at->lte(now()))->count(),
            'avg_ease_factor' => $cards->isEmpty() ? null : round($cards->avg('ease_factor'), 2),
            'total_reviews' => $reviews->count(),
            'success_rate' => $reviews->isEmpty() ? null : round(($reviews->where('quality', '>=', 3)->count() / $reviews->count()) * 100, 1),
            'by_deck' => $cards->groupBy('deck_id')->map->count(),
        ]);
    }
}