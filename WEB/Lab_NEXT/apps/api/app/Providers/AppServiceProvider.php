<?php

namespace App\Providers;

use App\Domains\CoreIdentityAndAccessKernel\Services\AuthServiceInterface;
use App\Domains\CoreIdentityAndAccessKernel\Services\EloquentAuthService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            AuthServiceInterface::class,
            EloquentAuthService::class
        );
    }

    public function boot(): void {}
}
