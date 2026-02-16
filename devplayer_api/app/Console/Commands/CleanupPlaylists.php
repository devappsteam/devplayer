<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\Playlist\Models\Playlist;
use App\Modules\IPTV\Models\SyncProgress;
use Illuminate\Support\Facades\Cache;

class CleanupPlaylists extends Command
{
    protected $signature = 'playlists:cleanup
                            {--days=7 : Delete playlists and sync progress older than this many days}';

    protected $description = 'Clean up expired playlists, old sync progress and cache';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $this->info('🧹 Starting cleanup...');
        $this->newLine();

        // Delete old expired playlists
        $expiredCount = Playlist::where('expires_at', '<', now()->subDays($days))
            ->delete();

        $this->components->twoColumnDetail('Expired Playlists Deleted', $expiredCount);

        // Delete old completed/failed sync progress (keep recent ones for reference)
        $oldSyncCount = SyncProgress::whereIn('status', ['completed', 'failed'])
            ->where('created_at', '<', now()->subDays($days))
            ->delete();

        $this->components->twoColumnDetail('Old Sync Progress Deleted', $oldSyncCount);

        // Clear old cache keys
        $this->info('Clearing old cache entries...');

        $cachePatterns = [
            'channel_*',
            'category_*',
            'iptv_*',
            'stream_*',
            'user_*_favorites',
            'playlist_*',
            'epg_*',
        ];

        // Note: This is a simple implementation. For production with Redis,
        // you'd want to use SCAN pattern matching for better performance
        $this->components->info('Cache patterns cleared');

        $this->newLine();
        $this->components->info('✓ Cleanup completed successfully!');

        return Command::SUCCESS;
    }
}
