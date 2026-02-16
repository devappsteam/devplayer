<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Favorite Module Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the Favorite module.
    |
    */

    'enabled' => env('FAVORITE_ENABLED', true),
    'pagination' => [
        'per_page' => env('FAVORITE_PER_PAGE', 15),
        'max_per_page' => env('FAVORITE_MAX_PER_PAGE', 100),
    ],
];