<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\IPTV\Models\SyncProgress;

class SyncIPTVStatus extends Command
{
    protected $signature = 'iptv:sync-status {sync_id : The sync ID to check}
                            {--watch : Watch progress in real-time}';
    protected $description = 'Check IPTV synchronization progress';

    public function handle(): int
    {
        $syncId = $this->argument('sync_id');
        $watch = $this->option('watch');

        if ($watch) {
            return $this->watchProgress($syncId);
        }

        return $this->displayProgress($syncId);
    }

    protected function displayProgress(string $syncId): int
    {
        $progress = SyncProgress::where('sync_id', $syncId)
            ->orderBy('type')
            ->get();

        if ($progress->isEmpty()) {
            $this->components->error('Sync not found');
            return Command::FAILURE;
        }

        $this->info('📊 Sync Progress: ' . $syncId);
        $this->newLine();

        foreach ($progress as $item) {
            $icon = match($item->status) {
                'completed' => '✅',
                'processing' => '⏳',
                'failed' => '❌',
                default => '⏸️',
            };

            $typeLabel = match($item->type) {
                'live' => 'TV Ao Vivo',
                'vod' => 'Filmes',
                'series' => 'Séries',
                default => $item->type,
            };

            $this->line("{$icon} {$typeLabel}");
            $this->components->twoColumnDetail('  Status', $item->status);
            $this->components->twoColumnDetail('  Step', $item->current_step ?? 'N/A');

            if ($item->total_items > 0) {
                $this->components->twoColumnDetail(
                    '  Progress',
                    "{$item->processed_items}/{$item->total_items} ({$item->progress_percentage}%)"
                );
            }

            $this->components->twoColumnDetail('  Message', $item->message ?? 'N/A');

            if ($item->error_message) {
                $this->components->error('  Error: ' . $item->error_message);
            }

            $this->newLine();
        }

        // Overall status
        $allCompleted = $progress->every(fn($p) => $p->status === 'completed');
        $anyFailed = $progress->contains(fn($p) => $p->status === 'failed');

        if ($allCompleted) {
            $this->components->info('🎉 All syncs completed successfully!');
        } elseif ($anyFailed) {
            $this->components->warn('⚠️  Some syncs failed');
            return Command::FAILURE;
        } else {
            $this->components->info('⏳ Sync in progress...');
        }

        return Command::SUCCESS;
    }

    protected function watchProgress(string $syncId): int
    {
        $this->info('👀 Watching sync progress (Ctrl+C to stop)...');
        $this->newLine();

        while (true) {
            // Clear screen
            if (DIRECTORY_SEPARATOR === '\\') {
                system('cls');
            } else {
                system('clear');
            }

            $result = $this->displayProgress($syncId);

            $progress = SyncProgress::where('sync_id', $syncId)->get();
            $allCompleted = $progress->every(fn($p) => $p->status === 'completed');
            $anyFailed = $progress->contains(fn($p) => $p->status === 'failed');

            if ($allCompleted || $anyFailed) {
                break;
            }

            sleep(2); // Update every 2 seconds
        }

        return Command::SUCCESS;
    }
}
