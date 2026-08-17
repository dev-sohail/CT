<?php

namespace App\Domains\SecretsPasswordAnd2FAVault\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SecretEntryResource extends JsonResource
{
    public function toArray($request): array
    {
        $reveal = $request->boolean('reveal');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'url' => $this->url,
            'username' => $this->username,
            'password' => $reveal ? $this->password : ($this->password ? '********' : null),
            'notes' => $reveal ? $this->notes : null,
            'tags' => $this->tags,
            'totp_enabled' => (bool) $this->totp_enabled,
            'strength_score' => $this->strength_score,
            'favorite' => (bool) $this->favorite,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'last_used_at' => $this->last_used_at?->toIso8601String(),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}