<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Category Module Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the Category module.
    |
    */

    'enabled' => env('CATEGORY_ENABLED', true),
    'pagination' => [
        'per_page' => env('CATEGORY_PER_PAGE', 15),
        'max_per_page' => env('CATEGORY_MAX_PER_PAGE', 100),
    ],
];