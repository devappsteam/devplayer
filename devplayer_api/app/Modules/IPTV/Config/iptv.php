<?php

return [
    /*
    |--------------------------------------------------------------------------
    | IPTV Module Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the IPTV module.
    |
    */

    'enabled' => env('IPTV_ENABLED', true),
    'pagination' => [
        'per_page' => env('IPTV_PER_PAGE', 15),
        'max_per_page' => env('IPTV_MAX_PER_PAGE', 100),
    ],
];