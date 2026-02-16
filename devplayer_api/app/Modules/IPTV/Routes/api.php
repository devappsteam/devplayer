<?php

use Illuminate\Support\Facades\Route;
use App\Modules\IPTV\Controllers\IPTVController;

// Custom routes (before resource routes)
Route::post('iptvs/test-connection', [IPTVController::class, 'testConnection']);
Route::post('iptvs/{uuid}/sync', [IPTVController::class, 'synchronize']);
Route::post('iptvs/{uuid}/sync-now', [IPTVController::class, 'synchronizeSync']);
Route::get('iptvs/{uuid}/stats', [IPTVController::class, 'stats']);
Route::get('sync/{syncId}/progress', [IPTVController::class, 'syncProgress']);

// Resource routes
Route::apiResource('iptvs', IPTVController::class)->parameters([
    'iptvs' => 'uuid'
]);
