<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\IPTV\Models\IPTV;
use App\Modules\IPTV\Services\IPTVService;
use App\Modules\Channel\Enums\StreamType;

class SyncIPTV extends Command
{
    protected $signature = 'iptv:sync {uuid : The UUID of the IPTV provider}
                            {--type=all : Type to sync: all, live, vod, series}
                            {--async : Run sync asynchronously using jobs}';
    protected $description = 'Synchronize IPTV channels, movies and series';

    public function __construct(
        private IPTVService $iptvService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $iptv = IPTV::where('uuid', $this->argument('uuid'))->firstOrFail();
        $type = $this->option('type');
        $async = $this->option('async');

        $this->info('🔄 Synchronizing: ' . $iptv->name);
        $this->info('   Type: ' . strtoupper($type));
        $this->info('   Mode: ' . ($async ? 'ASYNC (Jobs)' : 'SYNC (Direct)'));
        $this->newLine();

        try {
            if ($async) {
                return $this->handleAsyncSync($iptv, $type);
            }

            return $this->handleDirectSync($iptv, $type);

        } catch (\Exception $e) {
            $this->newLine();
            $this->components->error('Synchronization failed: ' . $e->getMessage());
            $this->line($e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    protected function handleAsyncSync(IPTV $iptv, string $type): int
    {
        // Determine types to sync
        $types = $type === 'all'
            ? [StreamType::LIVE, StreamType::VOD, StreamType::SERIES]
            : [StreamType::from($type)];

        $result = $this->iptvService->synchronizeAsync($iptv, $types);

        if (!$result['success']) {
            $this->components->error('Failed to start synchronization: ' . ($result['error'] ?? 'Unknown error'));
            return Command::FAILURE;
        }

        $this->components->info('✓ Synchronization jobs dispatched!');
        $this->components->twoColumnDetail('Sync ID', $result['sync_id']);
        $this->newLine();
        $this->info('Monitor progress with: php artisan iptv:sync-status ' . $result['sync_id']);

        return Command::SUCCESS;
    }

    protected function handleDirectSync(IPTV $iptv, string $type): int
    {
        if ($type !== 'all') {
            $this->components->warn('Direct sync with specific type not fully implemented. Running full sync...');
        }

        $this->withProgressBar(['live', 'vod', 'series'], function ($step) use ($iptv) {
            // This calls the synchronous version
            $this->iptvService->synchronize($iptv);
        });

        $this->newLine(2);

        $iptv->refresh();

        $this->components->info('✓ Synchronization completed successfully!');
        $this->newLine();

        // Show statistics by type
        $this->displayStats($iptv);

        return Command::SUCCESS;
    }

    protected function displayStats(IPTV $iptv): void
    {
        $liveCategories = $iptv->categories()->where('type', StreamType::LIVE->value)->count();
        $vodCategories = $iptv->categories()->where('type', StreamType::VOD->value)->count();
        $seriesCategories = $iptv->categories()->where('type', StreamType::SERIES->value)->count();

        $liveChannels = $iptv->channels()->where('stream_type', StreamType::LIVE)->count();
        $vodChannels = $iptv->channels()->where('stream_type', StreamType::VOD)->count();
        $seriesChannels = $iptv->channels()->where('stream_type', StreamType::SERIES)->count();

        $this->components->info('📺 Live TV:');
        $this->components->twoColumnDetail('  Categories', number_format($liveCategories));
        $this->components->twoColumnDetail('  Channels', number_format($liveChannels));
        $this->newLine();

        $this->components->info('🎬 Movies (VOD):');
        $this->components->twoColumnDetail('  Categories', number_format($vodCategories));
        $this->components->twoColumnDetail('  Movies', number_format($vodChannels));
        $this->newLine();

        $this->components->info('📺 Series:');
        $this->components->twoColumnDetail('  Categories', number_format($seriesCategories));
        $this->components->twoColumnDetail('  Series', number_format($seriesChannels));
        $this->newLine();

        $this->components->twoColumnDetail('Total Categories', number_format($iptv->categories()->count()));
        $this->components->twoColumnDetail('Total Content', number_format($iptv->channels()->count()));
        $this->components->twoColumnDetail('Last Sync', $iptv->last_sync_at?->diffForHumans() ?? 'Never');
    }
}
