<?php

namespace App\Domains\DashboardAndWidgetFramework\Contracts;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;

interface WidgetProvider
{
    public function key(): string;

    public function title(): string;

    public function description(): string;

    public function category(): string;

    public function defaultSizeX(): int;

    public function defaultSizeY(): int;

    public function refreshInterval(): int;

    public function provide(User $user): WidgetData;
}