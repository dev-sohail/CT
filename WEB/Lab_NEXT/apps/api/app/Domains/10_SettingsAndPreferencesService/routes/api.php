<?php

use App\Domains\SettingsAndPreferencesService\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings/batch', [SettingController::class, 'batch'])->name('settings.batch');
    Route::get('settings/{key}', [SettingController::class, 'show'])->name('settings.show');
    Route::put('settings/{key}', [SettingController::class, 'update'])->name('settings.update');
    Route::delete('settings/{key}', [SettingController::class, 'destroy'])->name('settings.destroy');
});
