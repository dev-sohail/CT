<?php

namespace App\Domains\AuditTrailAndActivityTimeline\Services;

use App\Domains\AuditTrailAndActivityTimeline\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditService
{
    public function record(
        int|Model|null $actor,
        string $action,
        ?Model $subject = null,
        array $meta = [],
        ?string $ip = null,
        ?string $userAgent = null
    ): AuditLog {
        $userId = match (true) {
            $actor instanceof Model => $actor->getKey(),
            is_int($actor) => $actor,
            default => null,
        };

        return AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject ? $subject->getKey() : null,
            'meta' => $meta ?: null,
            'ip' => $ip,
            'user_agent' => $userAgent ? mb_substr($userAgent, 0, 500) : null,
        ]);
    }

    public function recordFromRequest(Request $request, string $action, ?Model $subject = null, array $meta = [], ?Model $actor = null): AuditLog
    {
        return $this->record(
            $actor ?? $request->user(),
            $action,
            $subject,
            $meta,
            $request->ip(),
            $request->userAgent()
        );
    }
}
