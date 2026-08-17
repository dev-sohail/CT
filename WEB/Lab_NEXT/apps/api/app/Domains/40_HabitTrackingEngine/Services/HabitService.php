<?php

namespace App\Domains\HabitTrackingEngine\Services;

use App\Domains\HabitTrackingEngine\Models\Habit;
use App\Domains\HabitTrackingEngine\Models\HabitLog;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class HabitService
{
    public function log(Habit $habit, int $userId, array $data): HabitLog
    {
        return HabitLog::updateOrCreate(
            ['habit_id' => $habit->id, 'logged_for' => $data['logged_for']],
            [
                'user_id' => $userId,
                'count' => $data['count'] ?? 1,
                'completed' => $data['completed'] ?? true,
                'notes' => $data['notes'] ?? null,
            ],
        );
    }

    public function streak(Habit $habit): array
    {
        $days = $habit->logs()->where('completed', true)->pluck('logged_for')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())->unique()->sort()->values();

        $best = 0;
        $run = 0;
        $previous = null;
        foreach ($days as $day) {
            $date = Carbon::parse($day);
            $run = $previous && $date->isSameDay($previous->copy()->addDay()) ? $run + 1 : 1;
            $best = max($best, $run);
            $previous = $date;
        }

        $current = 0;
        $cursor = Carbon::today();
        while ($days->contains($cursor->toDateString())) {
            $current++;
            $cursor->subDay();
        }

        return ['current_streak' => $current, 'longest_streak' => $best, 'completed_days' => $days->count()];
    }

    public function today(Collection $habits, string $date): Collection
    {
        $ids = $habits->pluck('id');
        $logs = HabitLog::whereIn('habit_id', $ids)->whereDate('logged_for', $date)->get()->keyBy('habit_id');

        return $habits->map(function (Habit $habit) use ($logs) {
            $log = $logs->get($habit->id);
            return [
                'habit' => $habit,
                'log' => $log,
                'completed' => $log?->completed === true && $log->count >= $habit->target_count,
            ];
        });
    }
}
