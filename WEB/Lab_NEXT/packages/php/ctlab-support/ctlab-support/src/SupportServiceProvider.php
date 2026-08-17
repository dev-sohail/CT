<?php

declare(strict_types=1);

namespace Ctlab\Support;

use Illuminate\Support\ServiceProvider;

class SupportServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Package is purely structural — no config, views, or migrations.
    }

    public function register(): void
    {
        // Base classes are resolved via autoloading, not the container.
    }
}
