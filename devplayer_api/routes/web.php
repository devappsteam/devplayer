<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => config('app.name', 'DevPlayer API'),
        'version' => app()->version(),
        'time' => now()->toIso8601String(),
    ]);
});
