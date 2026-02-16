<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Channel\Controllers\ChannelController;
use App\Modules\Channel\Controllers\EpisodeController;

// Custom routes
Route::prefix('channels')->group(function () {
    Route::get('search', [ChannelController::class, 'search']);
    Route::get('live', [ChannelController::class, 'live']);
    Route::get('movies', [ChannelController::class, 'movies']);
    Route::get('vod', [ChannelController::class, 'movies']); // Alias
    Route::get('series', [ChannelController::class, 'series']);
    Route::get('type/{type}', [ChannelController::class, 'byType']);
    Route::get('category/{category_id}', [ChannelController::class, 'byCategory']);
    Route::post('{uuid}/favorite', [ChannelController::class, 'toggleFavorite']);
    Route::get('{uuid}/epg', [ChannelController::class, 'epg']);
    Route::get('{uuid}/stats', [ChannelController::class, 'statistics']);
    Route::get('{uuid}/stream', [ChannelController::class, 'getStream']);
    Route::get('{uuid}/episodes', [EpisodeController::class, 'byChannel']);
    Route::get('{uuid}/vod-info', [ChannelController::class, 'vodInfo']);
    Route::get('{uuid}/series-info', [ChannelController::class, 'seriesInfo']);
});

Route::apiResource('channels', ChannelController::class)->parameters([
    'channels' => 'uuid'
]);

// Episodes resource
Route::apiResource('episodes', EpisodeController::class)->parameters([
    'episodes' => 'uuid'
]);

