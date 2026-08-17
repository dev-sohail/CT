<?php

namespace App\Domains\SecretsPasswordAnd2FAVault\Http\Controllers;

use App\Domains\SecretsPasswordAnd2FAVault\Http\Resources\SecretEntryResource;
use App\Domains\SecretsPasswordAnd2FAVault\Models\SecretEntry;
use App\Domains\SecretsPasswordAnd2FAVault\Services\PasswordStrengthService;
use App\Domains\SecretsPasswordAnd2FAVault\Services\TotpService;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class SecretEntryController extends ApiController
{
    public function __construct(
        private readonly PasswordStrengthService $strength,
        private readonly TotpService $totp,
    ) {
    }

    public function index(Request $request)
    {
        $items = SecretEntry::forUser($request->user()->id)
            ->search($request->input('q'))
            ->when($request->input('category'), fn ($q, $c) => $q->where('category', $c))
            ->when($request->boolean('favorites'), fn ($q) => $q->where('favorite', true))
            ->when($request->boolean('weak'), fn ($q) => $q->where('strength_score', '<', 3))
            ->when($request->input('tag'), fn ($q, $t) => $q->whereJsonContains('tags', $t))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 25));

        return $this->respondPaginated($items, SecretEntryResource::class);
    }

    public function store(Request $request)
    {
        $data = $this->validateEntry($request);
        $entry = SecretEntry::create(array_merge(['user_id' => $request->user()->id], $data));
        $this->applySecretFields($entry, $request);

        return $this->respondCreated(SecretEntryResource::make($entry)->toArray($request));
    }

    public function show(Request $request, SecretEntry $secretEntry)
    {
        $this->authorizeOwner($request, $secretEntry);

        return $this->respondSuccess(SecretEntryResource::make($secretEntry)->toArray($request));
    }

    public function update(Request $request, SecretEntry $secretEntry)
    {
        $this->authorizeOwner($request, $secretEntry);
        $data = $this->validateEntry($request, true);
        if (!$request->has('password')) {
            unset($data['password']);
        }
        if (!$request->has('notes')) {
            unset($data['notes']);
        }
        if (!$request->has('totp_secret')) {
            unset($data['totp_secret']);
        }
        $secretEntry->update($data);
        $this->applySecretFields($secretEntry, $request);

        return $this->respondSuccess(SecretEntryResource::make($secretEntry)->toArray($request));
    }

    private function applySecretFields(SecretEntry $entry, Request $request): void
    {
        $changed = false;
        if ($request->has('password')) {
            $entry->password = $request->input('password');
            $entry->strength_score = $this->strength->score((string) $request->input('password'));
            $changed = true;
        }
        if ($request->has('notes')) {
            $entry->notes = $request->input('notes');
            $changed = true;
        }
        if ($request->has('totp_secret')) {
            $entry->totp_secret = $request->input('totp_secret');
            $entry->totp_enabled = $request->input('totp_secret') ? true : $entry->totp_enabled;
            $changed = true;
        }
        if ($changed) {
            $entry->save();
        }
    }

    public function destroy(Request $request, SecretEntry $secretEntry)
    {
        $this->authorizeOwner($request, $secretEntry);
        $secretEntry->delete();

        return $this->respondNoContent();
    }

    public function reveal(Request $request, SecretEntry $secretEntry)
    {
        $this->authorizeOwner($request, $secretEntry);
        $secretEntry->update(['last_used_at' => now()]);
        $request->merge(['reveal' => true]);

        return $this->respondSuccess(SecretEntryResource::make($secretEntry)->toArray($request));
    }

    public function totpCode(Request $request, SecretEntry $secretEntry)
    {
        $this->authorizeOwner($request, $secretEntry);

        if (!$secretEntry->totp_enabled || !$secretEntry->totp_secret) {
            return $this->respondError('TOTP is not enabled for this entry.', 422);
        }

        return $this->respondSuccess([
            'id' => $secretEntry->id,
            'code' => $this->totp->currentCode($secretEntry->totp_secret),
        ]);
    }

    public function totpVerify(Request $request, SecretEntry $secretEntry)
    {
        $this->authorizeOwner($request, $secretEntry);
        $request->validate(['code' => ['required', 'string', 'size:6']]);

        if (!$secretEntry->totp_enabled || !$secretEntry->totp_secret) {
            return $this->respondError('TOTP is not enabled for this entry.', 422);
        }

        $valid = $this->totp->verify($secretEntry->totp_secret, $request->input('code'));
        if (!$valid) {
            return $this->respondError('Invalid code.', 422);
        }

        $secretEntry->update(['last_used_at' => now()]);

        return $this->respondSuccess(['valid' => true]);
    }

    public function stats(Request $request)
    {
        $items = SecretEntry::forUser($request->user()->id)->get();

        return $this->respondSuccess([
            'total' => $items->count(),
            'by_category' => $items->groupBy('category')->map->count(),
            'favorites' => $items->where('favorite', true)->count(),
            'totp_enabled' => $items->where('totp_enabled', true)->count(),
            'weak' => $items->filter(fn (SecretEntry $e) => $e->strength_score !== null && $e->strength_score < 3)->count(),
            'average_strength' => $items->whereNotNull('strength_score')->isEmpty()
                ? null
                : round($items->whereNotNull('strength_score')->avg('strength_score'), 2),
        ]);
    }

    private function validateEntry(Request $request, bool $partial = false): array
    {
        $rules = [
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'category' => ['sometimes', 'string', 'max:64'],
            'url' => ['nullable', 'string', 'max:512'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:64'],
            'totp_secret' => ['nullable', 'string', 'max:512'],
            'totp_enabled' => ['boolean'],
            'favorite' => ['boolean'],
            'expires_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ];

        return $request->validate($rules);
    }

    private function authorizeOwner(Request $request, SecretEntry $entry): void
    {
        if ($entry->user_id !== $request->user()->id) {
            abort(404);
        }
    }
}