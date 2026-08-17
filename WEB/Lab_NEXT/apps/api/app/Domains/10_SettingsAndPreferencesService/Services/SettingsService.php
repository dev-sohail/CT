<?php

namespace App\Domains\SettingsAndPreferencesService\Services;

use App\Domains\SettingsAndPreferencesService\Models\Setting;

class SettingsService
{
    public function all(int $userId): array
    {
        return Setting::where('user_id', $userId)
            ->orderBy('key')
            ->get()
            ->pluck('value', 'key')
            ->all();
    }

    public function get(int $userId, string $key, mixed $default = null): mixed
    {
        $setting = Setting::where('user_id', $userId)->where('key', $key)->first();

        return $setting?->value ?? $default;
    }

    public function set(int $userId, string $key, mixed $value): Setting
    {
        return Setting::updateOrCreate(
            ['user_id' => $userId, 'key' => $key],
            ['value' => $value]
        );
    }

    public function setMany(int $userId, array $settings): array
    {
        $saved = [];
        foreach ($settings as $key => $value) {
            if (is_array($value)) {
                $saved[] = $this->set($userId, (string) $key, $value['value'] ?? null);
            } else {
                $saved[] = $this->set($userId, (string) $key, $value);
            }
        }

        return $saved;
    }

    public function forget(int $userId, string $key): void
    {
        Setting::where('user_id', $userId)->where('key', $key)->delete();
    }
}
