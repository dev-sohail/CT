<?php

namespace App\Domains\SettingsAndPreferencesService\Http\Resources;

use App\Domains\SettingsAndPreferencesService\Models\Setting;
use Ctlab\Support\Http\Resources\ApiResource;

/** @mixin Setting */
class SettingResource extends ApiResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'value' => $this->value,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
