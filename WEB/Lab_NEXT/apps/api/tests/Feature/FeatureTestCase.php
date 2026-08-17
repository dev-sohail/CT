<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class FeatureTestCase extends TestCase
{
    use RefreshDatabase;

    protected function seedOwner(string $email = 'owner@ctlab.local', string $password = 'admin123'): array
    {
        return [
            'email' => $email,
            'password' => $password,
        ];
    }

    protected function loginToken(string $email = 'owner@ctlab.local', string $password = 'admin123'): string
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => $email,
            'password' => $password,
        ]);

        $response->assertOk();

        return $response->json('meta.token');
    }
}
