<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Channel Module Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the Channel module.
    |
    */

    'enabled' => env('CHANNEL_ENABLED', true),
    'pagination' => [
        'per_page' => env('CHANNEL_PER_PAGE', 15),
        'max_per_page' => env('CHANNEL_MAX_PER_PAGE', 100),
    ],
];