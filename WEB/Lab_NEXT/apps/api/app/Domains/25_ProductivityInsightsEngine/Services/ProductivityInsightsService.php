<?php

namespace App\Domains\ProductivityInsightsEngine\Services;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\TimeAuditAndTimeBlockAnalyzer\Models\TimeEntry;
use App\Domains\UnifiedPlannerEngine\Models\PlannerItem;
use Carbon\Carbon;

class ProductivityInsightsService
{
    public function overview(User $user): array
    {
        $completed = PlannerItem::forUser($user->id)->where('status', 'completed')->count();
        $total = PlannerItem::forUser($user->id)->count();
        $incomplete = PlannerItem::forUser($user->id)->incomplete()->count();

        $weekStart = now()->startOfWeek();
        $completedThisWeek = PlannerItem::forUser($user->id)
            ->where('status', 'completed')
            ->where('completed_at', '>=', $weekStart)
            ->count();
        $completedToday = PlannerItem::forUser($user->id)
            ->where('status', 'completed')
            ->whereDate('completed_at', now()->toDateString())
            ->count();

        $avgDelay = $this->averageCompletionDelay($user);

        return [
            'planner' => [
                'total' => $total,
                'completed' => $completed,
                'incomplete' => $incomplete,
                'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
            ],
            'this_week' => [
                'completed' => $completedThisWeek,
                'completed_today' => $completedToday,
            ],
            'avg_completion_delay_days' => $avgDelay,
        ];
    }

    public function completionByScope(User $user): array
    {
        $items = PlannerItem::forUser($user->id)->select('scope', 'status')->get();

        $result = [];
        foreach (['day', 'week', 'month', 'quarter', 'year'] as $scope) {
            $scopeItems = $items->where('scope', $scope);
            $done = $scopeItems->where('status', 'completed')->count();
            $result[$scope] = [
                'total' => $scopeItems->count(),
                'completed' => $done,
                'rate' => $scopeItems->count() > 0 ? round(($done / $scopeItems->count()) * 100, 1) : 0,
            ];
        }

        return $result;
    }

    public function completionByPriority(User $user): array
    {
        $items = PlannerItem::forUser($user->id)->select('priority', 'status')->get();

        $result = [];
        foreach (['low', 'medium', 'high', 'urgent'] as $priority) {
            $scopeItems = $items->where('priority', $priority);
            $done = $scopeItems->where('status', 'completed')->count();
            $result[$priority] = [
                'total' => $scopeItems->count(),
                'completed' => $done,
                'rate' => $scopeItems->count() > 0 ? round(($done / $scopeItems->count()) * 100, 1) : 0,
            ];
        }

        return $result;
    }

    public function completionTrend(User $user, int $days = 14): array
    {
        $from = now()->subDays($days - 1)->startOfDay();

        $completed = PlannerItem::forUser($user->id)
            ->where('status', 'completed')
            ->where('completed_at', '>=', $from)
            ->get()
            ->groupBy(fn (PlannerItem $i) => $i->completed_at->toDateString());

        $points = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $from->copy()->addDays($i)->toDateString();
            $points[] = [
                'date' => $day,
                'completed' => $completed->get($day, collect())->count(),
            ];
        }

        return $points;
    }

    public function focusTime(User $user, int $days = 7): array
    {
        $from = now()->subDays($days - 1)->startOfDay();

        $entries = TimeEntry::forUser($user->id)
            ->where('started_at', '>=', $from)
            ->get();

        $byCategory = [];
        $totalMinutes = 0;
        foreach ($entries as $entry) {
            $minutes = $entry->durationMinutes();
            if ($minutes === null) {
                continue;
            }
            $totalMinutes += $minutes;
            $category = $entry->category ?? 'uncategorized';
            $byCategory[$category] = ($byCategory[$category] ?? 0) + $minutes;
        }
        arsort($byCategory);

        return [
            'days' => $days,
            'total_minutes' => $totalMinutes,
            'by_category' => $byCategory,
            'top_category' => array_key_first($byCategory),
        ];
    }

    private function averageCompletionDelay(User $user): ?float
    {
        $items = PlannerItem::forUser($user->id)
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->get();

        if ($items->isEmpty()) {
            return null;
        }

        $totalDays = $items->sum(function (PlannerItem $item) {
            return max(0, $item->created_at->diffInDays($item->completed_at, false));
        });

        return round($totalDays / $items->count(), 1);
    }
}