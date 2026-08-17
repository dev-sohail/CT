<?php

use App\Domains\AuditTrailAndActivityTimeline\Http\Controllers\AuditLogController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('audit-log', [AuditLogController::class, 'index'])->name('audit.index');
});
