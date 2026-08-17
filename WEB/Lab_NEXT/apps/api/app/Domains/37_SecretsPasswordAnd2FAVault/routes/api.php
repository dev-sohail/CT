<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('secret-entries/stats', [\App\Domains\SecretsPasswordAnd2FAVault\Http\Controllers\SecretEntryController::class, 'stats']);
    Route::get('secret-entries/{secretEntry}/totp', [\App\Domains\SecretsPasswordAnd2FAVault\Http\Controllers\SecretEntryController::class, 'totpCode']);
    Route::post('secret-entries/{secretEntry}/totp/verify', [\App\Domains\SecretsPasswordAnd2FAVault\Http\Controllers\SecretEntryController::class, 'totpVerify']);
    Route::get('secret-entries/{secretEntry}/reveal', [\App\Domains\SecretsPasswordAnd2FAVault\Http\Controllers\SecretEntryController::class, 'reveal']);

    Route::apiResource('secret-entries', \App\Domains\SecretsPasswordAnd2FAVault\Http\Controllers\SecretEntryController::class)
        ->parameter('secret-entries', 'secretEntry')
        ->names('secret-entries');
});