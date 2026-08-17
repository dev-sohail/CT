<?php

namespace App\Domains\CoreIdentityAndAccessKernel\Actions;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\CoreIdentityAndAccessKernel\Services\AuthServiceInterface;
use Ctlab\Support\Action;

class AssignRoleToUser extends Action
{
    public function __construct(private readonly AuthServiceInterface $auth) {}

    public function __invoke(User $user, string $role): User
    {
        return $this->auth->assignRole($user, $role);
    }
}
