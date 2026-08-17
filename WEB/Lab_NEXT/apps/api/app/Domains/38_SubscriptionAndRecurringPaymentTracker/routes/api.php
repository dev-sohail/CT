<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('subscriptions/stats', [\App\Domains\SubscriptionAndRecurringPaymentTracker\Http\Controllers\SubscriptionController::class, 'stats']);

    Route::apiResource('subscriptions', \App\Domains\SubscriptionAndRecurringPaymentTracker\Http\Controllers\SubscriptionController::class)
        ->parameter('subscriptions', 'subscription')
        ->names('subscriptions');
});