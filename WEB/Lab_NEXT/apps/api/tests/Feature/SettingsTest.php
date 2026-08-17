<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\SettingsAndPreferencesService\Models\Setting;
use App\Domains\SettingsAndPreferencesService\Services\SettingsService;
use Laravel\Sanctum\Sanctum;

class SettingsTest extends FeatureTestCase
{
    public function test_settings_are_scoped_per_user(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        Sanctum::actingAs($alice);
        $this->putJson('/api/v1/settings/theme', ['value' => 'dark'])
            ->assertOk()
            ->assertJsonPath('data.key', 'theme')
            ->assertJsonPath('data.value', 'dark');

        Sanctum::actingAs($alice);
        $this->getJson('/api/v1/settings')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        Sanctum::actingAs($bob);
        $this->getJson('/api/v1/settings')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_batch_update_and_show_single(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/settings/batch', [
            'settings' => [
                ['key' => 'notifications.enabled', 'value' => true],
                ['key' => 'daily_review_time', 'value' => '07:30'],
            ],
        ])->assertOk()->assertJsonCount(2, 'data');

        $this->getJson('/api/v1/settings/daily_review_time')
            ->assertOk()
            ->assertJsonPath('data.value', '07:30');

        $this->getJson('/api/v1/settings/missing')
            ->assertNotFound()
            ->assertJsonStructure(['data', 'meta', 'errors']);
    }

    public function test_delete_setting(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/settings/theme', ['value' => 'light'])->assertOk();
        $this->deleteJson('/api/v1/settings/theme')->assertNoContent();
        $this->getJson('/api/v1/settings/theme')->assertNotFound();
    }

    public function test_settings_require_auth(): void
    {
        $this->getJson('/api/v1/settings')->assertUnauthorized();
    }

    public function test_settings_service_helper(): void
    {
        $user = User::factory()->create();
        $service = app(SettingsService::class);

        $service->set($user->id, 'locale', 'en');
        $this->assertEquals('en', $service->get($user->id, 'locale'));
        $this->assertNull($service->get($user->id, 'nope'));
        $this->assertEquals('fallback', $service->get($user->id, 'nope', 'fallback'));

        $service->forget($user->id, 'locale');
        $service->forget($user->id, 'a');
        $service->setMany($user->id, ['a' => '1', 'b' => ['value' => '2']]);
        $this->assertEquals(['a' => '1', 'b' => '2'], $service->all($user->id));

        $service->forget($user->id, 'a');
        $this->assertCount(1, Setting::where('user_id', $user->id)->get());
    }
}
