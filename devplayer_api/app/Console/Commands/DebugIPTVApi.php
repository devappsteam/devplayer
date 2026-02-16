<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\IPTV\Models\IPTV;
use Illuminate\Support\Facades\Http;

class DebugIPTVApi extends Command
{
    protected $signature = 'iptv:debug {uuid : The UUID of the IPTV provider}';
    protected $description = 'Debug IPTV API responses';

    public function handle(): int
    {
        $iptv = IPTV::where('uuid', $this->argument('uuid'))->firstOrFail();

        $this->info('Debugging IPTV API: ' . $iptv->name);
        $this->line('URL: ' . $iptv->url);
        $this->line('Username: ' . $iptv->username);
        $this->newLine();

        // Test 1: Player API (authentication)
        $this->info('1️⃣  Testing Player API (Authentication)...');
        $playerUrl = rtrim($iptv->url, '/') . '/player_api.php';
        $this->line('   URL: ' . $playerUrl);
        
        try {
            $response = Http::timeout(30)->get($playerUrl, [
                'username' => $iptv->username,
                'password' => $iptv->password,
            ]);

            $this->components->twoColumnDetail('   Status Code', $response->status());
            $this->line('   Response Body:');
            $this->line('   ' . str_repeat('-', 60));
            dump($response->json());
            $this->line('   ' . str_repeat('-', 60));

        } catch (\Exception $e) {
            $this->components->error('   Error: ' . $e->getMessage());
        }

        $this->newLine();

        // Test 2: Get Live Categories
        $this->info('2️⃣  Testing Get Live Categories...');
        try {
            $response = Http::timeout(30)->get($playerUrl, [
                'username' => $iptv->username,
                'password' => $iptv->password,
                'action' => 'get_live_categories',
            ]);

            $this->components->twoColumnDetail('   Status Code', $response->status());
            $categories = $response->json();
            $this->components->twoColumnDetail('   Categories Found', is_array($categories) ? count($categories) : 0);
            
            if (is_array($categories) && count($categories) > 0) {
                $this->line('   First 3 categories:');
                foreach (array_slice($categories, 0, 3) as $cat) {
                    $this->line('   - ' . ($cat['category_name'] ?? 'N/A') . ' (ID: ' . ($cat['category_id'] ?? 'N/A') . ')');
                }
            }

        } catch (\Exception $e) {
            $this->components->error('   Error: ' . $e->getMessage());
        }

        $this->newLine();

        // Test 3: Get Live Streams
        $this->info('3️⃣  Testing Get Live Streams...');
        try {
            $response = Http::timeout(30)->get($playerUrl, [
                'username' => $iptv->username,
                'password' => $iptv->password,
                'action' => 'get_live_streams',
            ]);

            $this->components->twoColumnDetail('   Status Code', $response->status());
            $streams = $response->json();
            $this->components->twoColumnDetail('   Streams Found', is_array($streams) ? count($streams) : 0);
            
            if (is_array($streams) && count($streams) > 0) {
                $this->line('   First 3 streams:');
                foreach (array_slice($streams, 0, 3) as $stream) {
                    $this->line('   - ' . ($stream['name'] ?? 'N/A') . ' (ID: ' . ($stream['stream_id'] ?? 'N/A') . ')');
                }
            }

        } catch (\Exception $e) {
            $this->components->error('   Error: ' . $e->getMessage());
        }

        return Command::SUCCESS;
    }
}
