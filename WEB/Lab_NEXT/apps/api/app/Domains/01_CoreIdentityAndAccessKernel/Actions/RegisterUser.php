<?php

namespace App\Domains\CoreIdentityAndAccessKernel\Actions;

use App\Domains\CoreIdentityAndAccessKernel\DataTransferObjects\RegisterUserData;
use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\CoreIdentityAndAccessKernel\Services\AuthServiceInterface;
use Ctlab\Support\Action;

class RegisterUser extends Action
{
    public function __construct(private readonly AuthServiceInterface $auth) {}

    public function __invoke(RegisterUserData $data): User
    {
        return $this->auth->register($data->toArray());
    }
}
