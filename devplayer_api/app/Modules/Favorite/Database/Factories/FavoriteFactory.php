<?php

namespace App\Modules\Favorite\Database\Factories;

use App\Modules\Favorite\Models\Favorite;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Favorite>
 */
class FavoriteFactory extends Factory
{
    protected $model = Favorite::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}