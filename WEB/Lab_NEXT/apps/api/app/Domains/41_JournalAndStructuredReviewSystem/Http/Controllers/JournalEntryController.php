<?php

namespace App\Domains\JournalAndStructuredReviewSystem\Http\Controllers;

use App\Domains\JournalAndStructuredReviewSystem\Http\Resources\JournalEntryResource;
use App\Domains\JournalAndStructuredReviewSystem\Models\JournalEntry;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class JournalEntryController extends ApiController
{
    public function index(Request $request)
    {
        $items = JournalEntry::forUser($request->user()->id)
            ->search($request->input('q'))
            ->when($request->input('from'), fn ($q, $date) => $q->whereDate('entry_date', '>=', $date))
            ->when($request->input('to'), fn ($q, $date) => $q->whereDate('entry_date', '<=', $date))
            ->when($request->input('mood'), fn ($q, $mood) => $q->where('mood', $mood))
            ->when($request->input('tag'), fn ($q, $tag) => $q->whereJsonContains('tags', $tag))
            ->orderByDesc('entry_date')->orderByDesc('id')->paginate($request->integer('per_page', 25));

        return $this->respondPaginated($items, JournalEntryResource::class);
    }

    public function store(Request $request)
    {
        $entry = JournalEntry::create(array_merge(['user_id' => $request->user()->id], $this->validateEntry($request)));
        return $this->respondCreated(JournalEntryResource::make($entry)->toArray($request));
    }

    public function show(Request $request, JournalEntry $journalEntry)
    {
        $this->authorizeOwner($request, $journalEntry);
        return $this->respondSuccess(JournalEntryResource::make($journalEntry)->toArray($request));
    }

    public function update(Request $request, JournalEntry $journalEntry)
    {
        $this->authorizeOwner($request, $journalEntry);
        $journalEntry->update($this->validateEntry($request, true));
        return $this->respondSuccess(JournalEntryResource::make($journalEntry)->toArray($request));
    }

    public function destroy(Request $request, JournalEntry $journalEntry)
    {
        $this->authorizeOwner($request, $journalEntry);
        $journalEntry->delete();
        return $this->respondNoContent();
    }

    public function calendar(Request $request)
    {
        $items = JournalEntry::forUser($request->user()->id)
            ->whereBetween('entry_date', [$request->input('from', now()->startOfMonth()->toDateString()), $request->input('to', now()->endOfMonth()->toDateString())])
            ->orderBy('entry_date')->get(['id', 'entry_date', 'title', 'mood', 'energy']);
        return $this->respondSuccess($items);
    }

    public function stats(Request $request)
    {
        $items = JournalEntry::forUser($request->user()->id)->get();
        return $this->respondSuccess([
            'total' => $items->count(),
            'average_mood' => $items->whereNotNull('mood')->isEmpty() ? null : round($items->whereNotNull('mood')->avg('mood'), 2),
            'average_energy' => $items->whereNotNull('energy')->isEmpty() ? null : round($items->whereNotNull('energy')->avg('energy'), 2),
            'by_mood' => $items->whereNotNull('mood')->groupBy('mood')->map->count(),
            'tag_counts' => $items->flatMap(fn (JournalEntry $entry) => $entry->tags ?? [])->countBy(),
        ]);
    }

    private function validateEntry(Request $request, bool $partial = false): array
    {
        return $request->validate([
            'entry_date' => [$partial ? 'sometimes' : 'required', 'date'],
            'title' => ['nullable', 'string', 'max:255'],
            'body' => [$partial ? 'sometimes' : 'required', 'string'],
            'mood' => ['nullable', 'integer', 'between:1,10'],
            'energy' => ['nullable', 'integer', 'between:1,10'],
            'gratitude' => ['nullable', 'array'],
            'wins' => ['nullable', 'array'],
            'lessons' => ['nullable', 'array'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:64'],
            'is_private' => ['boolean'],
            'metadata' => ['nullable', 'array'],
        ]);
    }

    private function authorizeOwner(Request $request, JournalEntry $entry): void
    {
        if ($entry->user_id !== $request->user()->id) {
            abort(404);
        }
    }
}
