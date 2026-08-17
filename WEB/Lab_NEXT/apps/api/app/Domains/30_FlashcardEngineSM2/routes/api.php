<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('flashcards/stats', [\App\Domains\FlashcardEngineSM2\Http\Controllers\FlashcardController::class, 'stats']);
    Route::get('flashcards/due', [\App\Domains\FlashcardEngineSM2\Http\Controllers\FlashcardController::class, 'due']);

    Route::get('flashcards', [\App\Domains\FlashcardEngineSM2\Http\Controllers\FlashcardController::class, 'index']);
    Route::post('flashcards', [\App\Domains\FlashcardEngineSM2\Http\Controllers\FlashcardController::class, 'store']);
    Route::get('flashcards/{flashcard}', [\App\Domains\FlashcardEngineSM2\Http\Controllers\FlashcardController::class, 'show']);
    Route::put('flashcards/{flashcard}', [\App\Domains\FlashcardEngineSM2\Http\Controllers\FlashcardController::class, 'update']);
    Route::delete('flashcards/{flashcard}', [\App\Domains\FlashcardEngineSM2\Http\Controllers\FlashcardController::class, 'destroy']);
    Route::post('flashcards/{flashcard}/review', [\App\Domains\FlashcardEngineSM2\Http\Controllers\FlashcardController::class, 'review']);

    Route::get('decks', [\App\Domains\FlashcardEngineSM2\Http\Controllers\FlashcardController::class, 'decks']);
    Route::post('decks', [\App\Domains\FlashcardEngineSM2\Http\Controllers\FlashcardController::class, 'storeDeck']);
    Route::get('decks/{deck}', [\App\Domains\FlashcardEngineSM2\Http\Controllers\FlashcardController::class, 'showDeck']);
    Route::put('decks/{deck}', [\App\Domains\FlashcardEngineSM2\Http\Controllers\FlashcardController::class, 'updateDeck']);
    Route::delete('decks/{deck}', [\App\Domains\FlashcardEngineSM2\Http\Controllers\FlashcardController::class, 'destroyDeck']);
    Route::get('decks/{deck}/flashcards', [\App\Domains\FlashcardEngineSM2\Http\Controllers\FlashcardController::class, 'index']);
    Route::post('decks/{deck}/flashcards', [\App\Domains\FlashcardEngineSM2\Http\Controllers\FlashcardController::class, 'store']);
    Route::get('decks/{deck}/due', [\App\Domains\FlashcardEngineSM2\Http\Controllers\FlashcardController::class, 'due']);
});