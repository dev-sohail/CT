<?php

namespace App\Domains\CoreIdentityAndAccessKernel\Policies;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use Ctlab\Support\Policies\Policy;

class UserPolicy extends Policy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('users.view');
    }

    public function view(User $viewer, User $subject): bool
    {
        return $viewer->id === $subject->id || $viewer->hasPermission('users.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('users.create');
    }

    public function update(User $user, User $subject): bool
    {
        return $user->id === $subject->id || $user->hasPermission('users.update');
    }

    public function delete(User $user, User $subject): bool
    {
        return $user->hasPermission('users.delete') && $user->id !== $subject->id;
    }

    public function assignRole(User $user): bool
    {
        return $user->hasPermission('users.assign_role');
    }
}
