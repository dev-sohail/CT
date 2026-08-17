<?php

namespace App\Domains\SettingsAndPreferencesService\Http\Controllers;

use App\Domains\SettingsAndPreferencesService\Http\Resources\SettingResource;
use App\Domains\SettingsAndPreferencesService\Models\Setting;
use App\Domains\SettingsAndPreferencesService\Services\SettingsService;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class SettingController extends ApiController
{
    public function __construct(private readonly SettingsService $settings) {}

    public function index(Request $request)
    {
        $settings = Setting::where('user_id', $request->user()->id)->orderBy('key')->get();

        return $this->respondSuccess(SettingResource::collection($settings));
    }

    public function show(Request $request, string $key)
    {
        $setting = Setting::where('user_id', $request->user()->id)->where('key', $key)->first();

        if (! $setting) {
            return $this->respondError('Setting not found.', 404);
        }

        return $this->respondSuccess(SettingResource::make($setting));
    }

    public function update(Request $request, string $key)
    {
        $validated = $request->validate([
            'value' => ['nullable'],
        ]);

        $setting = $this->settings->set($request->user()->id, $key, $validated['value']);

        return $this->respondSuccess(SettingResource::make($setting->fresh()));
    }

    public function batch(Request $request)
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.key' => ['required', 'string', 'max:255'],
            'settings.*.value' => ['nullable'],
        ]);

        $saved = [];
        foreach ($validated['settings'] as $item) {
            $saved[] = $this->settings->set($request->user()->id, $item['key'], $item['value'] ?? null);
        }

        return $this->respondSuccess(SettingResource::collection(collect($saved)));
    }

    public function destroy(Request $request, string $key)
    {
        $this->settings->forget($request->user()->id, $key);

        return $this->respondNoContent();
    }
}
