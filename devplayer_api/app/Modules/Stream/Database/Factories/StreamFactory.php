<?php

namespace App\Modules\Stream\Database\Factories;

use App\Modules\Stream\Models\Stream;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Stream>
 */
class StreamFactory extends Factory
{
    protected $model = Stream::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}