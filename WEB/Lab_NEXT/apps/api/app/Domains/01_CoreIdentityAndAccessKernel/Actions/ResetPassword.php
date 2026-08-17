<?php

namespace App\Domains\CoreIdentityAndAccessKernel\Actions;

use App\Domains\CoreIdentityAndAccessKernel\Services\AuthServiceInterface;
use Ctlab\Support\Action;

class ResetPassword extends Action
{
    public function __construct(private readonly AuthServiceInterface $auth) {}

    public function __invoke(string $email, string $token, string $password): bool
    {
        return $this->auth->resetPassword($email, $token, $password);
    }
}
