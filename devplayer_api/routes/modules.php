<?php

use Illuminate\Support\Facades\Route;

// Load all module routes
$modules = [
    'IPTV',
    'Category',
    'Stream',
    'Playlist',
    'Channel',
    'Favorite',
    'History',
];

foreach ($modules as $module) {
    $path = base_path("app/Modules/{$module}/Routes/api.php");
    if (file_exists($path)) {
        require $path;
    }
}
