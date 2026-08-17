<?php

namespace App\Domains\ImportExportAndDataPortability\Providers;

use App\Domains\ImportExportAndDataPortability\Services\DataTransferService;
use App\Domains\ImportExportAndDataPortability\Services\Providers\CalendarDataTransferProvider;
use App\Domains\ImportExportAndDataPortability\Services\Providers\ContactsDataTransferProvider;
use Illuminate\Support\ServiceProvider;

class ImportExportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DataTransferService::class, function ($app) {
            return new DataTransferService([
                $app->make(ContactsDataTransferProvider::class),
                $app->make(CalendarDataTransferProvider::class),
            ]);
        });
    }

    public function boot(): void
    {
        //
    }
}