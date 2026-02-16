<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Modules\IPTV\Models\IPTV;
use App\Modules\IPTV\Enums\PlaylistProvider;
use App\Modules\IPTV\Enums\IPTVStatus;
use Illuminate\Support\Facades\Hash;

class DevelopmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test user
        $user = User::firstOrCreate(
            ['email' => 'admin@devplayer.test'],
            [
                'name' => 'Admin DevPlayer',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('✓ User created: admin@devplayer.test / password');

        // Create IPTV provider with real credentials
        $iptv = IPTV::updateOrCreate(
            [
                'user_id' => $user->id,
                'url' => 'http://pfsv.io',
            ],
            [
                'name' => 'PFSV IPTV',
                'provider_type' => PlaylistProvider::XTREAM,
                'username' => '96618418',
                'password' => '06760',
                'status' => IPTVStatus::ACTIVE,
            ]
        );

        $this->command->info('✓ IPTV Provider created: PFSV IPTV');
        $this->command->info('  UUID: ' . $iptv->uuid);
        $this->command->newLine();
        $this->command->info('To sync channels run:');
        $this->command->line('  curl -X POST http://localhost:8000/api/v1/iptvs/' . $iptv->uuid . '/sync');
    }
}
