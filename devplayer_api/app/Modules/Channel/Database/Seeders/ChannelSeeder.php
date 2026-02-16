<?php

namespace App\Modules\Channel\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Channel\Models\Channel;

class ChannelSeeder extends Seeder
{
    public function run(): void
    {
        // Create sample channels
        Channel::factory()->count(10)->create();
    }
}