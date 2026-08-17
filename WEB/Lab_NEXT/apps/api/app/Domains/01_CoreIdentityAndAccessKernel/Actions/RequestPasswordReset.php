<?php

namespace App\Domains\CoreIdentityAndAccessKernel\Actions;

use App\Domains\CoreIdentityAndAccessKernel\Services\AuthServiceInterface;
use Ctlab\Support\Action;

class RequestPasswordReset extends Action
{
    public function __construct(private readonly AuthServiceInterface $auth) {}

    public function __invoke(string $email): void
    {
        $this->auth->requestPasswordReset($email);
    }
}
