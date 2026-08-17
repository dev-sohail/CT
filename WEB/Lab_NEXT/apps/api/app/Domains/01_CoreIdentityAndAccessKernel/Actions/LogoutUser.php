<?php

namespace App\Domains\CoreIdentityAndAccessKernel\Actions;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\CoreIdentityAndAccessKernel\Services\AuthServiceInterface;
use Ctlab\Support\Action;

class LogoutUser extends Action
{
    public function __construct(private readonly AuthServiceInterface $auth) {}

    public function __invoke(User $user, ?string $tokenId = null): void
    {
        $this->auth->logout($user, $tokenId);
    }
}
