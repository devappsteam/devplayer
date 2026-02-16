<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Category\Controllers\CategoryController;


Route::apiResource('categories', CategoryController::class)->parameters([
    'categories' => 'uuid'
]);