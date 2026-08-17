<?php

namespace App\Domains\CoreIdentityAndAccessKernel\DataTransferObjects;

use Ctlab\Support\DataTransferObject;

class LoginData extends DataTransferObject
{
    public string $email;

    public string $password;

    public bool $remember = false;
}
