<?php

namespace App\Domains\CoreIdentityAndAccessKernel\Traits;

use App\Domains\CoreIdentityAndAccessKernel\Models\Permission;
use App\Domains\CoreIdentityAndAccessKernel\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

trait HasRoles
{
    public function roles(): BelongsToMany
    {
        return $this->morphToMany(Role::class, 'model', 'identity_model_has_roles')
            ->withTimestamps();
    }

    public function assignRole(string|Role $role): void
    {
        $role = is_string($role) ? Role::where('slug', $role)->firstOrFail() : $role;
        $this->roles()->syncWithoutDetaching([$role->id]);
        $this->forgetPermissionCache();
    }

    public function syncRoles(array $roles): void
    {
        $ids = collect($roles)->map(fn ($r) => is_string($r) ? Role::where('slug', $r)->firstOrFail()->id : $r->id);
        $this->roles()->sync($ids);
        $this->forgetPermissionCache();
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles->contains('slug', $slug);
    }

    public function hasPermission(string $permission): bool
    {
        return $this->permissions()->contains('slug', $permission);
    }

    public function permissions(): Collection
    {
        return $this->cachedPermissions();
    }

    protected function cachedPermissions(): Collection
    {
        return Cache::remember(
            "user:{$this->id}:permissions",
            now()->addHour(),
            fn () => Permission::query()
                ->join('identity_role_permission', 'identity_role_permission.permission_id', '=', 'identity_permissions.id')
                ->join('identity_model_has_roles', 'identity_model_has_roles.role_id', '=', 'identity_role_permission.role_id')
                ->where('identity_model_has_roles.model_id', $this->id)
                ->where('identity_model_has_roles.model_type', $this->getMorphClass())
                ->distinct()
                ->pluck('identity_permissions.slug')
        );
    }

    public function forgetPermissionCache(): void
    {
        Cache::forget("user:{$this->id}:permissions");
    }
}
