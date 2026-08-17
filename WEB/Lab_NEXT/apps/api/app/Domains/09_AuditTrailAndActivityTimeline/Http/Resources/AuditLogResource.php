<?php

namespace App\Domains\AuditTrailAndActivityTimeline\Http\Resources;

use App\Domains\AuditTrailAndActivityTimeline\Models\AuditLog;
use Ctlab\Support\Http\Resources\ApiResource;

/** @mixin AuditLog */
class AuditLogResource extends ApiResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'meta' => $this->meta,
            'ip' => $this->ip,
            'created_at' => $this->created_at,
        ];
    }
}
