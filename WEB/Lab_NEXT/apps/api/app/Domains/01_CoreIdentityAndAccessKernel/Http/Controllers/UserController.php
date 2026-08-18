<?php

namespace App\Domains\CoreIdentityAndAccessKernel\Http\Controllers;

use App\Domains\CoreIdentityAndAccessKernel\Http\Resources\UserResource;
use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends ApiController
{
    public function index(Request $request)
    {
        $users = User::with('roles')->latest()->get();

        return $this->respondSuccess(
            $users->map(fn (User $u) => UserResource::make($u)->toArray($request))
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:identity_users,email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
            'role_id' => ['required', 'integer', 'exists:identity_roles,id'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $user->roles()->attach($validated['role_id']);

        return $this->respondCreated(
            UserResource::make($user->load('roles'))->toArray($request)
        );
    }

    public function show(Request $request, User $user)
    {
        $user->load('roles');

        return $this->respondSuccess(
            UserResource::make($user)->toArray($request)
        );
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:identity_users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:12'],
            'role_id' => ['sometimes', 'integer', 'exists:identity_roles,id'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (isset($validated['password']) && $validated['password'] !== null) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        if (isset($validated['role_id'])) {
            $user->roles()->sync([$validated['role_id']]);
        }

        return $this->respondSuccess(
            UserResource::make($user->load('roles'))->toArray($request)
        );
    }

    public function destroy(User $user)
    {
        $user->roles()->detach();
        $user->delete();

        return $this->respondNoContent();
    }
}
