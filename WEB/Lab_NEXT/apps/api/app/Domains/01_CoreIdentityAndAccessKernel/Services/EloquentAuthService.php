<?php

namespace App\Domains\CoreIdentityAndAccessKernel\Services;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class EloquentAuthService implements AuthServiceInterface
{
    public function register(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->assignRole($data['role'] ?? 'member');

        event(new Registered($user));

        return $user;
    }

    public function authenticate(string $email, string $password, bool $remember = false): ?User
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        if (! $user->is_active) {
            return null;
        }

        return $user;
    }

    public function issueToken(User $user, string $deviceName, array $abilities = ['*']): string
    {
        return $user->createToken($deviceName, $abilities)->plainTextToken;
    }

    public function logout(User $user, ?string $tokenId = null): void
    {
        if ($tokenId) {
            $user->tokens()->where('id', $tokenId)->delete();

            return;
        }

        $user->currentAccessToken()?->delete();
    }

    public function requestPasswordReset(string $email): void
    {
        Password::sendResetLink(['email' => $email]);
    }

    public function resetPassword(string $email, string $token, string $password): bool
    {
        $status = Password::reset(
            ['email' => $email, 'token' => $token, 'password' => $password],
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));
                $user->save();
            }
        );

        return $status === Password::PASSWORD_RESET;
    }

    public function assignRole(User $user, string $role): User
    {
        $user->assignRole($role);

        return $user;
    }
}
