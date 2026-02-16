<?php

namespace App\Modules\Playlist\Database\Factories;

use App\Modules\Playlist\Models\Playlist;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Playlist>
 */
class PlaylistFactory extends Factory
{
    protected $model = Playlist::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}