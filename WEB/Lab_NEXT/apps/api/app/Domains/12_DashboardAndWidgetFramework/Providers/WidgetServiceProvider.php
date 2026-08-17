<?php

namespace App\Domains\DashboardAndWidgetFramework\Providers;

use App\Domains\DashboardAndWidgetFramework\Services\WidgetRegistry;
use App\Domains\DashboardAndWidgetFramework\Widgets\BirthdaysWidget;
use App\Domains\DashboardAndWidgetFramework\Widgets\OverviewStatsWidget;
use App\Domains\DashboardAndWidgetFramework\Widgets\RecentActivityWidget;
use App\Domains\DashboardAndWidgetFramework\Widgets\UpcomingEventsWidget;
use Illuminate\Support\ServiceProvider;

class WidgetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag([
            UpcomingEventsWidget::class,
            BirthdaysWidget::class,
            RecentActivityWidget::class,
            OverviewStatsWidget::class,
        ], 'ctlab.widget');

        $this->app->singleton(WidgetRegistry::class, function ($app) {
            $registry = new WidgetRegistry();

            // Tagged discovery: any provider tagged 'ctlab.widget' is auto-registered.
            foreach ($app->tagged('ctlab.widget') as $provider) {
                $registry->register($provider);
            }

            return $registry;
        });
    }

    public function boot(): void
    {
        //
    }
}