<?php

namespace App\Domains\ImportExportAndDataPortability\Services\Providers;

use App\Domains\CalendarAndSchedulingKernel\Models\CalendarEvent;
use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\ImportExportAndDataPortability\Contracts\DataTransferProvider;

class CalendarDataTransferProvider implements DataTransferProvider
{
    public function domain(): string
    {
        return 'calendar';
    }

    public function export(User $user): array
    {
        $events = CalendarEvent::where('user_id', $user->id)->get();

        return [
            'events' => $events->map(fn (CalendarEvent $e) => [
                'title' => $e->title,
                'description' => $e->description,
                'location' => $e->location,
                'starts_at' => $e->starts_at?->toIso8601String(),
                'ends_at' => $e->ends_at?->toIso8601String(),
                'is_all_day' => (bool) $e->is_all_day,
                'timezone' => $e->timezone,
                'status' => $e->status,
                'recurrence_rule' => $e->recurrence_rule,
                'recurrence_ends_at' => $e->recurrence_ends_at?->toIso8601String(),
                'metadata' => $e->metadata,
            ])->values(),
        ];
    }

    public function import(User $user, array $data): array
    {
        $counts = ['events' => 0];

        foreach ($data['events'] ?? [] as $row) {
            CalendarEvent::create([
                'user_id' => $user->id,
                'title' => $row['title'] ?? 'Untitled',
                'description' => $row['description'] ?? null,
                'location' => $row['location'] ?? null,
                'starts_at' => isset($row['starts_at']) ? \Carbon\Carbon::parse($row['starts_at']) : now(),
                'ends_at' => isset($row['ends_at']) ? \Carbon\Carbon::parse($row['ends_at']) : null,
                'is_all_day' => $row['is_all_day'] ?? false,
                'timezone' => $row['timezone'] ?? 'UTC',
                'status' => $row['status'] ?? 'confirmed',
                'recurrence_rule' => $row['recurrence_rule'] ?? null,
                'recurrence_ends_at' => isset($row['recurrence_ends_at']) ? \Carbon\Carbon::parse($row['recurrence_ends_at']) : null,
                'metadata' => $row['metadata'] ?? null,
            ]);
            $counts['events']++;
        }

        return $counts;
    }
}