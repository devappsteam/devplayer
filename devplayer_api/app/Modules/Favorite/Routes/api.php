<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Favorite\Controllers\FavoriteController;

// Custom routes for favorites
Route::get('favorites/user/{userId}', [FavoriteController::class, 'userFavorites']);
Route::middleware('auth:api')->group(function () {
    Route::get('favorites/me', [FavoriteController::class, 'userFavoritesMe']);
    Route::get('favorites/me/check/{channelId}', [FavoriteController::class, 'checkMe']);
    Route::post('favorites/me/toggle', [FavoriteController::class, 'toggleMe']);
});
Route::post('favorites/sync', [FavoriteController::class, 'sync']);
Route::post('favorites/toggle', [FavoriteController::class, 'toggle']);
Route::post('favorites/fix-stream-types', [FavoriteController::class, 'fixStreamTypes']);
Route::get('favorites/user/{userId}/check/{channelId}', [FavoriteController::class, 'check']);

// Standard resource routes
Route::apiResource('favorites', FavoriteController::class)->parameters([
    'favorites' => 'uuid'
]);
