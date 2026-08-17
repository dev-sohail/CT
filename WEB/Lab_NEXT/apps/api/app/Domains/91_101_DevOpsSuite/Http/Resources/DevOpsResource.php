<?php

namespace App\Domains\DevOpsSuite\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DevOpsResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id' => $this->id, 'record_type' => $this->record_type, 'name' => $this->name, 'status' => $this->status, 'environment' => $this->environment, 'url' => $this->url, 'description' => $this->description, 'config' => $this->config, 'metadata' => $this->metadata];
    }
}
