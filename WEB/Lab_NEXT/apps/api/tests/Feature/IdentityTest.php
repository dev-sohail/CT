<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Database\Seeders\RolePermissionSeeder;
use App\Domains\CoreIdentityAndAccessKernel\Models\User;

class IdentityTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_cannot_access_me_endpoint(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Alice',
            'email' => 'alice@ctlab.local',
            'password' => 'StrongPass!123',
            'password_confirmation' => 'StrongPass!123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'alice@ctlab.local')
            ->assertJsonStructure(['data' => ['id', 'name', 'email'], 'meta' => ['token']]);

        $this->assertDatabaseHas('identity_users', ['email' => 'alice@ctlab.local']);
    }

    public function test_register_validates_password_confirmation(): void
    {
        $this->postJson('/api/v1/register', [
            'name' => 'Alice',
            'email' => 'alice@ctlab.local',
            'password' => 'StrongPass!123',
            'password_confirmation' => 'DifferentPass!123',
        ])->assertStatus(422);

        $this->assertDatabaseMissing('identity_users', ['email' => 'alice@ctlab.local']);
    }

    public function test_register_rejects_short_password(): void
    {
        $this->postJson('/api/v1/register', [
            'name' => 'Alice',
            'email' => 'alice@ctlab.local',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertStatus(422);
    }

    public function test_user_can_login_and_access_me(): void
    {
        $this->postJson('/api/v1/register', [
            'name' => 'Alice',
            'email' => 'alice@ctlab.local',
            'password' => 'StrongPass!123',
            'password_confirmation' => 'StrongPass!123',
        ])->assertCreated();

        $login = $this->postJson('/api/v1/login', [
            'email' => 'alice@ctlab.local',
            'password' => 'StrongPass!123',
        ])->assertOk();

        $token = $login->json('meta.token');
        $this->assertNotEmpty($token);

        $this->withToken($token)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'alice@ctlab.local');
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $this->postJson('/api/v1/login', [
            'email' => 'nobody@ctlab.local',
            'password' => 'wrong-password',
        ])->assertStatus(401);
    }

    public function test_logout_revokes_token(): void
    {
        $user = User::factory()->create();

        $this->withToken($user->createToken('t')->plainTextToken)
            ->postJson('/api/v1/logout')
            ->assertStatus(204);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_me_requires_valid_token(): void
    {
        $this->withToken('bogus-token')
            ->getJson('/api/v1/me')
            ->assertStatus(401);
    }

    public function test_owner_login_works_after_seed(): void
    {
        $login = $this->postJson('/api/v1/login', [
            'email' => 'owner@ctlab.local',
            'password' => 'admin123',
        ])->assertOk();

        $this->assertNotEmpty($login->json('meta.token'));
    }

    public function test_validation_errors_use_envelope(): void
    {
        $this->postJson('/api/v1/login', ['email' => 'not-an-email'])
            ->assertStatus(422)
            ->assertJsonStructure(['data', 'meta', 'errors']);
    }
}
