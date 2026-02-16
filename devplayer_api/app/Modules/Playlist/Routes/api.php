<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Playlist\Controllers\PlaylistController;


Route::apiResource('playlists', PlaylistController::class)->parameters([
    'playlists' => 'uuid'
]);