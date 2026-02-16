<?php

namespace App\Modules\Stream\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Stream\Models\Stream;

class StreamSeeder extends Seeder
{
    public function run(): void
    {
        // Create sample streams
        Stream::factory()->count(10)->create();
    }
}