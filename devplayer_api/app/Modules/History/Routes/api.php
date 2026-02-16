<?php

use Illuminate\Support\Facades\Route;
use App\Modules\History\Controllers\HistoryController;

Route::prefix('history')->group(function () {
    // Background sync endpoint (PWA)
    Route::post('/sync', [HistoryController::class, 'sync']);

    // Get last watched item for a user (optionally filtered by content type)
    Route::get('/user/{userId}/last', [HistoryController::class, 'last']);
    Route::get('/user/{userId}/last/{contentType}', [HistoryController::class, 'last']);

    // Get all history for a user (optionally filtered by content type)
    Route::get('/user/{userId}', [HistoryController::class, 'index']);
    Route::get('/user/{userId}/{contentType}', [HistoryController::class, 'index']);

    // Add to history
    Route::post('/user/{userId}', [HistoryController::class, 'store']);

    // Clear history
    Route::delete('/user/{userId}', [HistoryController::class, 'clear']);
});
