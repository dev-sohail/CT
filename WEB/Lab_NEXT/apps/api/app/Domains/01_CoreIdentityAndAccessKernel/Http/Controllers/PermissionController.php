<?php

namespace App\Domains\CoreIdentityAndAccessKernel\Http\Controllers;

use App\Domains\CoreIdentityAndAccessKernel\Models\Permission;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PermissionController extends ApiController
{
    public function index()
    {
        return $this->respondSuccess(Permission::orderBy('group')->orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:identity_permissions,slug'],
            'group' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $permission = Permission::create($validated);

        return $this->respondCreated($permission);
    }

    public function show(Permission $permission)
    {
        return $this->respondSuccess($permission);
    }

    public function update(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:identity_permissions,slug,' . $permission->id],
            'group' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $permission->update($validated);

        return $this->respondSuccess($permission);
    }

    public function destroy(Permission $permission)
    {
        $permission->roles()->detach();
        $permission->delete();

        return $this->respondNoContent();
    }
}
