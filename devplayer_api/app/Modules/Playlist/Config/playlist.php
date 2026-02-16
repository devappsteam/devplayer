<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Playlist Module Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the Playlist module.
    |
    */

    'enabled' => env('PLAYLIST_ENABLED', true),
    'pagination' => [
        'per_page' => env('PLAYLIST_PER_PAGE', 15),
        'max_per_page' => env('PLAYLIST_MAX_PER_PAGE', 100),
    ],
];