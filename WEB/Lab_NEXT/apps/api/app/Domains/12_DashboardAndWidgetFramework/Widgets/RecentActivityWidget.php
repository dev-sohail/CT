<?php

namespace App\Domains\DashboardAndWidgetFramework\Widgets;

use App\Domains\AuditTrailAndActivityTimeline\Models\AuditLog;
use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\DashboardAndWidgetFramework\Contracts\WidgetData;
use App\Domains\DashboardAndWidgetFramework\Contracts\WidgetProvider;

class RecentActivityWidget implements WidgetProvider
{
    public function key(): string
    {
        return 'recent-activity';
    }

    public function title(): string
    {
        return 'Recent Activity';
    }

    public function description(): string
    {
        return 'Your latest audited actions across the platform.';
    }

    public function category(): string
    {
        return 'system';
    }

    public function defaultSizeX(): int
    {
        return 2;
    }

    public function defaultSizeY(): int
    {
        return 3;
    }

    public function refreshInterval(): int
    {
        return 600;
    }

    public function provide(User $user): WidgetData
    {
        $logs = AuditLog::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'subject_type' => $log->subject_type,
                'meta' => $log->meta,
                'created_at' => $log->created_at?->toIso8601String(),
            ]);

        return new WidgetData(
            key: $this->key(),
            title: $this->title(),
            data: $logs->values()->all(),
            refresh_interval: $this->refreshInterval(),
        );
    }
}