<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('home/summary', [\App\Domains\PersonalDashboardLifeOSHome\Http\Controllers\HomeController::class, 'summary']);
});