<?php

namespace App\Modules\IPTV\Database\Factories;

use App\Modules\IPTV\Models\IPTV;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<IPTV>
 */
class IPTVFactory extends Factory
{
    protected $model = IPTV::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}