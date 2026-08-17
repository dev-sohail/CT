<?php

namespace App\Domains\SecretsPasswordAnd2FAVault\Services;

class PasswordStrengthService
{
    public function score(string $password): int
    {
        if ($password === '') {
            return 0;
        }

        $score = 1;
        if (strlen($password) >= 8) {
            $score++;
        }
        if (preg_match('/[A-Z]/', $password) && preg_match('/[a-z]/', $password)) {
            $score++;
        }
        if (preg_match('/\d/', $password)) {
            $score++;
        }
        if (preg_match('/[^A-Za-z0-9]/', $password)) {
            $score++;
        }
        return min(5, $score);
    }
}
