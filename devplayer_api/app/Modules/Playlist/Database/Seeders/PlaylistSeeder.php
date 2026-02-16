<?php

namespace App\Modules\Playlist\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Playlist\Models\Playlist;

class PlaylistSeeder extends Seeder
{
    public function run(): void
    {
        // Create sample playlists
        Playlist::factory()->count(10)->create();
    }
}