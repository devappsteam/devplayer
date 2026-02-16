<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Favorite\Controllers\FavoriteController;

// Custom routes for favorites
Route::get('favorites/user/{userId}', [FavoriteController::class, 'userFavorites']);
Route::post('favorites/sync', [FavoriteController::class, 'sync']);
Route::post('favorites/toggle', [FavoriteController::class, 'toggle']);
Route::get('favorites/user/{userId}/check/{channelId}', [FavoriteController::class, 'check']);

// Standard resource routes
Route::apiResource('favorites', FavoriteController::class)->parameters([
    'favorites' => 'uuid'
]);
