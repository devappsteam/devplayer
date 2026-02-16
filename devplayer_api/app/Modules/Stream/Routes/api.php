<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Stream\Controllers\StreamController;
use App\Modules\Stream\Controllers\VideoProxyController;

// Rotas de proxy de vídeo (sem autenticação para melhor compatibilidade)
Route::get('video-proxy', [VideoProxyController::class, 'proxy']);
Route::get('video-stream', [VideoProxyController::class, 'stream']);

// Rotas de streams padrão
Route::apiResource('streams', StreamController::class)->parameters([
    'streams' => 'uuid'
]);
