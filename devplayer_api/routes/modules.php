<?php

use Illuminate\Support\Facades\Route;

// Load all module routes
$modules = [
    'Auth',
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
        try {
            require $path;
        } catch (\Throwable $e) {
            \Log::error("Error loading module routes for {$module}", [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e; // Re-throw para debug
        }
    }
}
