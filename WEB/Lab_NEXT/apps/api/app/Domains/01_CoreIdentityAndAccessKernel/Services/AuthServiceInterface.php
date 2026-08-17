<?php

namespace App\Domains\CoreIdentityAndAccessKernel\Services;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;

interface AuthServiceInterface
{
    public function register(array $data): User;

    public function authenticate(string $email, string $password, bool $remember = false): ?User;

    public function issueToken(User $user, string $deviceName, array $abilities = ['*']): string;

    public function logout(User $user, ?string $tokenId = null): void;

    public function requestPasswordReset(string $email): void;

    public function resetPassword(string $email, string $token, string $password): bool;

    public function assignRole(User $user, string $role): User;
}
