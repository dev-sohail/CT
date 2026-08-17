<?php

namespace App\Domains\StudyAndCourseTracker\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'subject' => $this->subject,
            'url' => $this->url,
            'provider' => $this->provider,
            'status' => $this->status,
            'hours_target' => $this->hours_target,
            'hours_spent' => $this->hours_spent,
            'progress_percent' => $this->progressPercent(),
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}