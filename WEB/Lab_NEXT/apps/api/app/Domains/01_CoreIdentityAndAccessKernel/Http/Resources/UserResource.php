<?php

namespace App\Domains\CoreIdentityAndAccessKernel\Http\Resources;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use Ctlab\Support\Http\Resources\ApiResource;

/** @mixin User */
class UserResource extends ApiResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'email_verified_at' => $this->email_verified_at,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('slug')),
            'permissions' => $this->whenLoaded('roles', fn () => $this->cachedPermissions ?? []),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
