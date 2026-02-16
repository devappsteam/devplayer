<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Stream Module Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the Stream module.
    |
    */

    'enabled' => env('STREAM_ENABLED', true),
    'pagination' => [
        'per_page' => env('STREAM_PER_PAGE', 15),
        'max_per_page' => env('STREAM_MAX_PER_PAGE', 100),
    ],
];