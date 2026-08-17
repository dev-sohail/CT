<?php

namespace App\Domains\NotificationHubAndEventBus\Http\Controllers;

use App\Domains\NotificationHubAndEventBus\Http\Resources\NotificationPreferenceResource;
use App\Domains\NotificationHubAndEventBus\Models\NotificationPreference;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationPreferenceController extends ApiController
{
    public function index(Request $request)
    {
        $preferences = NotificationPreference::where('user_id', $request->user()->id)
            ->orderBy('type')
            ->get();

        return $this->respondSuccess(NotificationPreferenceResource::collection($preferences));
    }

    public function update(Request $request, string $type)
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'channels' => ['nullable', 'array'],
            'channels.*' => ['string', Rule::in(['database', 'mail'])],
        ]);

        $preference = NotificationPreference::updateOrCreate(
            ['user_id' => $request->user()->id, 'type' => $type],
            [
                'enabled' => $data['enabled'],
                'channels' => $data['channels'] ?? NotificationPreference::DEFAULT_CHANNELS,
            ]);

        return $this->respondSuccess(NotificationPreferenceResource::make($preference)->toArray($request));
    }
}
