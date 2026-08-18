<?php

namespace App\Domains\CoreIdentityAndAccessKernel\Http\Controllers;

use App\Domains\CoreIdentityAndAccessKernel\Models\Permission;
use App\Domains\CoreIdentityAndAccessKernel\Models\Role;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class RoleController extends ApiController
{
    public function index()
    {
        $roles = Role::with('permissions')->get();

        return $this->respondSuccess($roles);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:identity_roles,name'],
            'slug' => ['required', 'string', 'max:255', 'unique:identity_roles,slug'],
            'description' => ['nullable', 'string'],
            'is_system' => ['sometimes', 'boolean'],
            'permission_ids' => ['sometimes', 'array'],
            'permission_ids.*' => ['integer', 'exists:identity_permissions,id'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'is_system' => $validated['is_system'] ?? false,
        ]);

        if (!empty($validated['permission_ids'])) {
            $role->permissions()->sync($validated['permission_ids']);
        }

        return $this->respondCreated($role->load('permissions'));
    }

    public function show(Role $role)
    {
        return $this->respondSuccess($role->load('permissions'));
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255', 'unique:identity_roles,name,' . $role->id],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:identity_roles,slug,' . $role->id],
            'description' => ['nullable', 'string'],
            'is_system' => ['sometimes', 'boolean'],
            'permission_ids' => ['sometimes', 'array'],
            'permission_ids.*' => ['integer', 'exists:identity_permissions,id'],
        ]);

        $role->update($validated);

        if (isset($validated['permission_ids'])) {
            $role->permissions()->sync($validated['permission_ids']);
        }

        return $this->respondSuccess($role->load('permissions'));
    }

    public function destroy(Role $role)
    {
        if ($role->is_system) {
            return $this->respondError('System roles cannot be deleted', 403);
        }

        $role->permissions()->detach();
        $role->users()->detach();
        $role->delete();

        return $this->respondNoContent();
    }
}
