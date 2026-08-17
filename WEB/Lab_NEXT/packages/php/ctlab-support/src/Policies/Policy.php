<?php

namespace Ctlab\Support\Policies;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Auth\User;

abstract class Policy
{
    protected function allow(string $message = ''): Response
    {
        return Response::allow($message);
    }

    protected function deny(string $message = '', int $code = 403): Response
    {
        return Response::deny($message, $code);
    }

    protected function authorize(User $user, bool $condition, string $message = ''): Response
    {
        return $condition ? $this->allow() : $this->deny($message);
    }
}
