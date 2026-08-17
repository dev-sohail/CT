<?php

namespace App\Domains\CoreIdentityAndAccessKernel\Actions;

use App\Domains\CoreIdentityAndAccessKernel\DataTransferObjects\LoginData;
use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\CoreIdentityAndAccessKernel\Services\AuthServiceInterface;
use Ctlab\Support\Action;

class AuthenticateUser extends Action
{
    public function __construct(private readonly AuthServiceInterface $auth) {}

    public function __invoke(LoginData $data): ?User
    {
        return $this->auth->authenticate($data->email, $data->password, $data->remember);
    }
}
