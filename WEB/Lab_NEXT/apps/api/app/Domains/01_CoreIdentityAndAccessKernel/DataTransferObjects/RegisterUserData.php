<?php

namespace App\Domains\CoreIdentityAndAccessKernel\DataTransferObjects;

use Ctlab\Support\DataTransferObject;

class RegisterUserData extends DataTransferObject
{
    public string $name;

    public string $email;

    public string $password;

    public ?string $role = null;
}
