<?php

use App\Domains\CalendarAndSchedulingKernel\Http\Controllers\CalendarEventController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('calendar/events', [CalendarEventController::class, 'index'])->name('calendar.events.index');
    Route::post('calendar/events', [CalendarEventController::class, 'store'])->name('calendar.events.store');
    Route::get('calendar/events/expand', [CalendarEventController::class, 'expand'])->name('calendar.events.expand');
    Route::get('calendar/events/{event}', [CalendarEventController::class, 'show'])->name('calendar.events.show');
    Route::put('calendar/events/{event}', [CalendarEventController::class, 'update'])->name('calendar.events.update');
    Route::delete('calendar/events/{event}', [CalendarEventController::class, 'destroy'])->name('calendar.events.destroy');
});