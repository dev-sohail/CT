<?php

namespace App\Domains\ReceiptAndWarrantyArchive\Http\Controllers;

use App\Domains\ReceiptAndWarrantyArchive\Http\Resources\ReceiptResource;
use App\Domains\ReceiptAndWarrantyArchive\Models\Receipt;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class ReceiptController extends ApiController
{
    public function index(Request $request)
    {
        $items = Receipt::forUser($request->user()->id)
            ->with('document')
            ->search($request->input('q'))
            ->when($request->input('category'), fn ($q, $c) => $q->where('category', $c))
            ->when($request->input('merchant'), fn ($q, $m) => $q->where('merchant', $m))
            ->orderByDesc('purchased_at')
            ->paginate($request->integer('per_page', 25));

        return $this->respondPaginated($items, ReceiptResource::class);
    }

    public function store(Request $request)
    {
        $receipt = Receipt::create(array_merge(['user_id' => $request->user()->id], $this->validateReceipt($request)));

        return $this->respondCreated(ReceiptResource::make($receipt)->toArray($request));
    }

    public function show(Request $request, Receipt $receipt)
    {
        $this->authorizeOwner($request, $receipt);

        return $this->respondSuccess(ReceiptResource::make($receipt)->toArray($request));
    }

    public function update(Request $request, Receipt $receipt)
    {
        $this->authorizeOwner($request, $receipt);
        $receipt->update($this->validateReceipt($request, true));

        return $this->respondSuccess(ReceiptResource::make($receipt)->toArray($request));
    }

    public function destroy(Request $request, Receipt $receipt)
    {
        $this->authorizeOwner($request, $receipt);
        $receipt->delete();

        return $this->respondNoContent();
    }

    public function warranties(Request $request)
    {
        $items = Receipt::forUser($request->user()->id)
            ->whereNotNull('warranty_until')
            ->where('warranty_until', '>=', now()->toDateString())
            ->orderBy('warranty_until')
            ->get();

        return $this->respondSuccess(ReceiptResource::collection($items));
    }

    public function stats(Request $request)
    {
        $items = Receipt::forUser($request->user()->id)->get();

        return $this->respondSuccess([
            'total' => $items->count(),
            'total_spent' => round((float) $items->sum('amount'), 2),
            'by_category' => $items->groupBy('category')->map(fn ($g) => round((float) $g->sum('amount'), 2)),
            'active_warranties' => $items->filter(fn (Receipt $r) => $r->warranty_until && $r->warranty_until->gte(now()->startOfDay()))->count(),
            'warranties_expiring_soon' => $items
                ->filter(fn (Receipt $r) => $r->warranty_until && $r->warranty_until->between(now(), now()->addDays(30)))
                ->count(),
        ]);
    }

    private function validateReceipt(Request $request, bool $partial = false): array
    {
        $rules = [
            'title' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'merchant' => ['nullable', 'string', 'max:128'],
            'category' => ['nullable', 'string', 'max:64'],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'purchased_at' => ['nullable', 'date'],
            'warranty_until' => ['nullable', 'date', 'after_or_equal:purchased_at'],
            'receipt_number' => ['nullable', 'string', 'max:128'],
            'items' => ['nullable', 'array'],
            'document_id' => ['nullable', 'integer', 'exists:documents,id'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];

        return $request->validate($rules);
    }

    private function authorizeOwner(Request $request, Receipt $receipt): void
    {
        if ($receipt->user_id !== $request->user()->id) {
            abort(404);
        }
    }
}