<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('widgets', [\App\Domains\DashboardAndWidgetFramework\Http\Controllers\WidgetController::class, 'index']);
    Route::get('widgets/{key}/data', [\App\Domains\DashboardAndWidgetFramework\Http\Controllers\WidgetController::class, 'data']);

    Route::get('dashboard/layout', [\App\Domains\DashboardAndWidgetFramework\Http\Controllers\WidgetController::class, 'layout']);
    Route::put('dashboard/layout', [\App\Domains\DashboardAndWidgetFramework\Http\Controllers\WidgetController::class, 'saveLayout']);
    Route::post('dashboard/layout/{key}', [\App\Domains\DashboardAndWidgetFramework\Http\Controllers\WidgetController::class, 'add']);
    Route::delete('dashboard/layout/{key}', [\App\Domains\DashboardAndWidgetFramework\Http\Controllers\WidgetController::class, 'remove']);
});