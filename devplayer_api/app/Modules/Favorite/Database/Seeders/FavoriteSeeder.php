<?php

namespace App\Modules\Favorite\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Favorite\Models\Favorite;

class FavoriteSeeder extends Seeder
{
    public function run(): void
    {
        // Create sample favorites
        Favorite::factory()->count(10)->create();
    }
}