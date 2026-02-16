<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Stream\Controllers\StreamController;


Route::apiResource('streams', StreamController::class)->parameters([
    'streams' => 'uuid'
]);