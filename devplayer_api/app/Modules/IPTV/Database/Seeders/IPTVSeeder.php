<?php

namespace App\Modules\IPTV\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\IPTV\Models\IPTV;

class IPTVSeeder extends Seeder
{
    public function run(): void
    {
        // Create sample iPTVs
        IPTV::factory()->count(10)->create();
    }
}