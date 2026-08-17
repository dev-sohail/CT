<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('contacts/birthday-soon', [\App\Domains\ContactsAndRelationshipGraph\Http\Controllers\PersonController::class, 'birthdaySoon']);

    Route::apiResource('contacts', \App\Domains\ContactsAndRelationshipGraph\Http\Controllers\PersonController::class)
        ->parameter('contacts', 'person');

    Route::get('contacts/{person}/relationships', [\App\Domains\ContactsAndRelationshipGraph\Http\Controllers\PersonController::class, 'relationships']);
    Route::post('contacts/{person}/relationships', [\App\Domains\ContactsAndRelationshipGraph\Http\Controllers\PersonController::class, 'addRelationship']);
    Route::delete('contacts/{person}/relationships/{relationship}', [\App\Domains\ContactsAndRelationshipGraph\Http\Controllers\PersonController::class, 'removeRelationship']);
});