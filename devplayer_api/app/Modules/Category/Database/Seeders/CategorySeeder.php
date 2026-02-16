<?php

namespace App\Modules\Category\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Category\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Create sample categories
        Category::factory()->count(10)->create();
    }
}