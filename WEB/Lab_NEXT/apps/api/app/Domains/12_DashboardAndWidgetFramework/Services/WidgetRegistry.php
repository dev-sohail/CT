<?php

namespace App\Domains\DashboardAndWidgetFramework\Services;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\DashboardAndWidgetFramework\Contracts\WidgetData;
use App\Domains\DashboardAndWidgetFramework\Contracts\WidgetProvider;
use App\Domains\DashboardAndWidgetFramework\Models\UserWidgetLayout;
use App\Domains\DashboardAndWidgetFramework\Models\WidgetRegistration;
use Illuminate\Support\Collection;

class WidgetRegistry
{
    /** @var array<string, WidgetProvider> */
    private array $providers = [];

    public function __construct(array $providers = [])
    {
        foreach ($providers as $provider) {
            $this->register($provider);
        }
    }

    public function register(WidgetProvider $provider): void
    {
        $this->providers[$provider->key()] = $provider;
    }

    /** @return array<string, WidgetProvider> */
    public function providers(): array
    {
        return $this->providers;
    }

    public function has(string $key): bool
    {
        return isset($this->providers[$key]);
    }

    public function provider(string $key): ?WidgetProvider
    {
        return $this->providers[$key] ?? null;
    }

    public function provide(User $user, string $key): ?WidgetData
    {
        $provider = $this->provider($key);
        if (!$provider) {
            return null;
        }
        return $provider->provide($user);
    }

    public function registered(): Collection
    {
        return WidgetRegistration::orderBy('category')->orderBy('title')->get();
    }

    public function syncRegistrations(): void
    {
        foreach ($this->providers as $provider) {
            WidgetRegistration::updateOrCreate(
                ['key' => $provider->key()],
                [
                    'title' => $provider->title(),
                    'description' => $provider->description(),
                    'category' => $provider->category(),
                    'default_size_x' => $provider->defaultSizeX(),
                    'default_size_y' => $provider->defaultSizeY(),
                    'refresh_interval' => $provider->refreshInterval(),
                ]
            );
        }
    }

    public function layout(User $user): Collection
    {
        return UserWidgetLayout::where('user_id', $user->id)->orderBy('position_y')->orderBy('position_x')->get();
    }

    public function setLayout(User $user, array $items): void
    {
        foreach ($items as $item) {
            $key = $item['widget_key'] ?? null;
            if (!$key || !$this->has($key)) {
                continue;
            }
            UserWidgetLayout::updateOrCreate(
                ['user_id' => $user->id, 'widget_key' => $key],
                [
                    'position_x' => $item['position_x'] ?? 0,
                    'position_y' => $item['position_y'] ?? 0,
                    'width' => $item['width'] ?? null,
                    'height' => $item['height'] ?? null,
                    'enabled' => $item['enabled'] ?? true,
                    'refresh_interval' => $item['refresh_interval'] ?? null,
                ]
            );
        }
    }

    public function addToLayout(User $user, string $key): ?UserWidgetLayout
    {
        $provider = $this->provider($key);
        if (!$provider) {
            return null;
        }

        $count = UserWidgetLayout::where('user_id', $user->id)->count();

        return UserWidgetLayout::create([
            'user_id' => $user->id,
            'widget_key' => $key,
            'position_x' => 0,
            'position_y' => $count,
            'width' => $provider->defaultSizeX(),
            'height' => $provider->defaultSizeY(),
            'enabled' => true,
        ]);
    }

    public function removeFromLayout(User $user, string $key): bool
    {
        return (bool) UserWidgetLayout::where('user_id', $user->id)->where('widget_key', $key)->delete();
    }

    public function ensureDefaultLayout(User $user): void
    {
        $existing = UserWidgetLayout::where('user_id', $user->id)->count();
        if ($existing > 0) {
            return;
        }

        $position = 0;
        foreach ($this->providers as $provider) {
            UserWidgetLayout::create([
                'user_id' => $user->id,
                'widget_key' => $provider->key(),
                'position_x' => 0,
                'position_y' => $position++,
                'width' => $provider->defaultSizeX(),
                'height' => $provider->defaultSizeY(),
                'enabled' => true,
            ]);
        }
    }
}