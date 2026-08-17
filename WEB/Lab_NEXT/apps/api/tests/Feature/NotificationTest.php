<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\NotificationHubAndEventBus\Models\NotificationPreference;
use App\Domains\NotificationHubAndEventBus\Notifications\GenericNotification;
use App\Domains\NotificationHubAndEventBus\Services\NotificationDispatcher;
use Laravel\Sanctum\Sanctum;

class NotificationTest extends FeatureTestCase
{
    private function seedNotification(User $user, string $title = 'Reminder', string $body = 'Pay rent'): void
    {
        $user->notify(new GenericNotification($title, $body, '/dashboard'));
    }

    public function test_dispatch_sends_database_notification_by_default(): void
    {
        $user = User::factory()->create();

        app(NotificationDispatcher::class)->dispatch($user, new GenericNotification('Hi', 'There'), 'reminder');

        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseHas('notifications', ['type' => GenericNotification::class]);
    }

    public function test_dispatch_respects_disabled_preference(): void
    {
        $user = User::factory()->create();

        $dispatcher = app(NotificationDispatcher::class);
        $dispatcher->dispatch($user, new GenericNotification('On', 'First'), 'alert');

        app(NotificationPreference::class)
            ->where('user_id', $user->id)->where('type', 'alert')->update(['enabled' => false]);

        $dispatcher->dispatch($user, new GenericNotification('Off', 'Second'), 'alert');

        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_notifications_index_unread_count_and_read_flow(): void
    {
        $user = User::factory()->create();
        $this->seedNotification($user);
        $this->seedNotification($user, 'Second', 'Two');

        Sanctum::actingAs($user);
        $this->getJson('/api/v1/notifications')->assertOk()->assertJsonCount(2, 'data');

        $this->getJson('/api/v1/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.count', 2);

        $firstId = $user->notifications()->latest('created_at')->first()->id;
        $this->postJson("/api/v1/notifications/{$firstId}/read")
            ->assertOk()
            ->assertJsonPath('data.read_at', fn ($value) => $value !== null);

        $this->getJson('/api/v1/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.count', 1);
    }

    public function test_mark_all_read_and_delete(): void
    {
        $user = User::factory()->create();
        $this->seedNotification($user);
        $this->seedNotification($user, 'Second', 'Two');

        Sanctum::actingAs($user);
        $this->postJson('/api/v1/notifications/read-all')->assertNoContent();
        $this->getJson('/api/v1/notifications/unread-count')->assertJsonPath('data.count', 0);

        $id = $user->notifications()->first()->id;
        $this->deleteJson("/api/v1/notifications/{$id}")->assertNoContent();
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_notifications_are_scoped_to_owner(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $this->seedNotification($alice);

        Sanctum::actingAs($bob);
        $this->getJson('/api/v1/notifications')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_preferences_upsert_and_validation(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/notification-preferences/reminder', [
            'enabled' => true,
            'channels' => ['database', 'mail'],
        ])->assertOk()->assertJsonPath('data.enabled', true);

        $this->getJson('/api/v1/notification-preferences')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->putJson('/api/v1/notification-preferences/reminder', ['enabled' => false])
            ->assertOk()
            ->assertJsonPath('data.enabled', false);

        $this->putJson('/api/v1/notification-preferences/reminder', ['enabled' => true, 'channels' => ['telegram']])
            ->assertUnprocessable();
    }
}
