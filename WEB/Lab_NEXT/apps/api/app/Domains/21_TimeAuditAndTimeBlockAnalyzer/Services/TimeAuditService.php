<?php

namespace App\Domains\TimeAuditAndTimeBlockAnalyzer\Services;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\TimeAuditAndTimeBlockAnalyzer\Models\TimeEntry;
use Carbon\Carbon;

class TimeAuditService
{
    /**
     * Aggregate time entries in a period by category and by day.
     */
    public function analytics(User $user, Carbon $from, Carbon $to): array
    {
        $entries = TimeEntry::forUser($user->id)
            ->where('started_at', '>=', $from)
            ->where('started_at', '<=', $to)
            ->get();

        $byCategory = [];
        $byDay = [];
        $byHour = array_fill(0, 24, 0);
        $totalMinutes = 0;

        foreach ($entries as $entry) {
            $minutes = $entry->durationMinutes();
            if ($minutes === null) {
                continue;
            }
            $totalMinutes += $minutes;

            $category = $entry->category ?? 'uncategorized';
            $byCategory[$category] = ($byCategory[$category] ?? 0) + $minutes;

            $day = $entry->started_at->toDateString();
            $byDay[$day] = ($byDay[$day] ?? 0) + $minutes;

            $byHour[(int) $entry->started_at->format('H')] += $minutes;
        }

        arsort($byCategory);

        return [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'days' => (int) $from->diffInDays($to) + 1,
            ],
            'total_minutes' => $totalMinutes,
            'total_hours' => round($totalMinutes / 60, 2),
            'by_category' => $byCategory,
            'by_day' => $byDay,
            'by_hour' => $byHour,
            'top_category' => array_key_first($byCategory),
        ];
    }
}