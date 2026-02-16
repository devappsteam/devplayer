<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\IPTV\Models\IPTV;
use App\Modules\IPTV\Helpers\XtreamApiClient;

class TestIPTVConnection extends Command
{
    protected $signature = 'iptv:test {uuid : The UUID of the IPTV provider}';
    protected $description = 'Test IPTV connection and display provider information';

    public function handle(): int
    {
        $iptv = IPTV::where('uuid', $this->argument('uuid'))->firstOrFail();

        $this->info('Testing connection to: ' . $iptv->name);
        $this->line('URL: ' . $iptv->url);
        $this->newLine();

        $client = new XtreamApiClient($iptv->url, $iptv->username, $iptv->password);

        try {
            $this->info('🔄 Testing connection...');
            $info = $client->testConnection();

            $this->components->twoColumnDetail('Status', '<fg=green>✓ Connected</>');
            $this->components->twoColumnDetail('Username', $info['user_info']['username'] ?? 'N/A');
            $this->components->twoColumnDetail('Subscription Status', $info['user_info']['status'] ?? 'N/A');
            $this->components->twoColumnDetail('Expiration Date', $info['user_info']['exp_date'] ?? 'N/A');
            $this->components->twoColumnDetail('Active Connections', $info['user_info']['active_cons'] ?? 'N/A');
            $this->components->twoColumnDetail('Max Connections', $info['user_info']['max_connections'] ?? 'N/A');
            $this->newLine();

            $this->info('📊 Server Information:');
            $this->components->twoColumnDetail('Server URL', $info['server_info']['url'] ?? 'N/A');
            $this->components->twoColumnDetail('Server Port', $info['server_info']['port'] ?? 'N/A');
            $this->components->twoColumnDetail('HTTPS Port', $info['server_info']['https_port'] ?? 'N/A');
            $this->components->twoColumnDetail('Server Protocol', $info['server_info']['server_protocol'] ?? 'N/A');
            $this->components->twoColumnDetail('Timezone', $info['server_info']['timezone'] ?? 'N/A');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->components->error('Connection failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
