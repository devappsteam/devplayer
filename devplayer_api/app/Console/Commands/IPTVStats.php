<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\IPTV\Models\IPTV;
use App\Modules\Category\Models\Category;
use App\Modules\Channel\Models\Channel;
use App\Modules\Channel\Enums\StreamType;

class IPTVStats extends Command
{
    protected $signature = 'iptv:stats {uuid? : The UUID of the IPTV provider}';
    protected $description = 'Display IPTV statistics (Live TV, Movies, Series)';

    public function handle(): int
    {
        $uuid = $this->argument('uuid');

        if ($uuid) {
            return $this->showProviderStats($uuid);
        }

        return $this->showGlobalStats();
    }

    protected function showProviderStats(string $uuid): int
    {
        $iptv = IPTV::where('uuid', $uuid)->firstOrFail();

        $this->info('📊 IPTV Statistics: ' . $iptv->name);
        $this->newLine();

        // Live TV
        $liveCategories = $iptv->categories()->where('type', StreamType::LIVE->value)->count();
        $liveChannels = $iptv->channels()->where('stream_type', StreamType::LIVE)->count();

        $this->components->info('📺 Live TV:');
        $this->components->twoColumnDetail('  Categories', $liveCategories);
        $this->components->twoColumnDetail('  Channels', number_format($liveChannels));
        $this->newLine();

        // VOD (Movies)
        $vodCategories = $iptv->categories()->where('type', StreamType::VOD->value)->count();
        $vodMovies = $iptv->channels()->where('stream_type', StreamType::VOD)->count();

        $this->components->info('🎬 Movies (VOD):');
        $this->components->twoColumnDetail('  Categories', $vodCategories);
        $this->components->twoColumnDetail('  Movies', number_format($vodMovies));

        if ($vodMovies > 0) {
            // Show sample movies
            $sampleMovies = $iptv->channels()
                ->where('stream_type', StreamType::VOD)
                ->with('category')
                ->inRandomOrder()
                ->take(3)
                ->get();

            $this->line('  Sample Movies:');
            foreach ($sampleMovies as $movie) {
                $this->line('    • ' . $movie->name . ' [' . ($movie->category?->name ?? 'No Category') . ']');
            }
        }

        $this->newLine();

        // Series
        $seriesCategories = $iptv->categories()->where('type', StreamType::SERIES->value)->count();
        $seriesCount = $iptv->channels()->where('stream_type', StreamType::SERIES)->count();

        $this->components->info('📺 Series:');
        $this->components->twoColumnDetail('  Categories', $seriesCategories);
        $this->components->twoColumnDetail('  Series', number_format($seriesCount));

        if ($seriesCount > 0) {
            // Show sample series
            $sampleSeries = $iptv->channels()
                ->where('stream_type', StreamType::SERIES)
                ->with('category')
                ->inRandomOrder()
                ->take(3)
                ->get();

            $this->line('  Sample Series:');
            foreach ($sampleSeries as $series) {
                $this->line('    • ' . $series->name . ' [' . ($series->category?->name ?? 'No Category') . ']');
            }
        }

        $this->newLine();

        // Totals
        $this->components->twoColumnDetail('Total Categories', number_format($iptv->categories()->count()));
        $this->components->twoColumnDetail('Total Content', number_format($iptv->channels()->count()));
        $this->components->twoColumnDetail('Status', $iptv->status->name);
        $this->components->twoColumnDetail('Last Sync', $iptv->last_sync_at?->diffForHumans() ?? 'Never');

        return Command::SUCCESS;
    }

    protected function showGlobalStats(): int
    {
        $totalIptvs = IPTV::count();
        $activeIptvs = IPTV::where('status', 'active')->count();

        $this->info('📊 Global IPTV Statistics');
        $this->newLine();

        $this->components->twoColumnDetail('Total Providers', $totalIptvs);
        $this->components->twoColumnDetail('Active Providers', $activeIptvs);
        $this->newLine();

        // Live TV
        $liveChannels = Channel::where('stream_type', StreamType::LIVE)->count();
        $this->components->info('📺 Live TV: ' . number_format($liveChannels) . ' channels');

        // VOD
        $vodMovies = Channel::where('stream_type', StreamType::VOD)->count();
        $this->components->info('🎬 Movies: ' . number_format($vodMovies) . ' movies');

        // Series
        $seriesCount = Channel::where('stream_type', StreamType::SERIES)->count();
        $this->components->info('📺 Series: ' . number_format($seriesCount) . ' series');

        $this->newLine();
        $this->components->twoColumnDetail('Total Content', number_format(Channel::count()));

        return Command::SUCCESS;
    }
}
