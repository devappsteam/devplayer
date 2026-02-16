<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\Stream\Models\Stream;
use App\Modules\Stream\Services\StreamService;

class HealthCheckStreams extends Command
{
    protected $signature = 'streams:health-check
                            {--limit=100 : Maximum number of streams to check}
                            {--force : Force check even if recently checked}';

    protected $description = 'Perform health checks on streams';

    public function __construct(
        private StreamService $streamService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $force = $this->option('force');

        $this->info('🔍 Starting stream health checks...');
        $this->newLine();

        $query = Stream::query();

        if (!$force) {
            // Only check streams that need checking
            $query->where(function ($q) {
                $q->whereNull('last_check_at')
                  ->orWhere('last_check_at', '<', now()->subMinutes(30));
            });
        }

        $streams = $query->limit($limit)->get();

        if ($streams->isEmpty()) {
            $this->components->info('No streams need health check at this time.');
            return Command::SUCCESS;
        }

        $this->components->info("Checking {$streams->count()} streams...");
        $this->newLine();

        $healthy = 0;
        $unhealthy = 0;

        $this->withProgressBar($streams, function ($stream) use (&$healthy, &$unhealthy) {
            $isHealthy = $this->streamService->checkHealth($stream);

            if ($isHealthy) {
                $healthy++;
            } else {
                $unhealthy++;
            }
        });

        $this->newLine(2);

        $this->components->info('Health check completed!');
        $this->newLine();

        $this->components->twoColumnDetail('Total Checked', $streams->count());
        $this->components->twoColumnDetail('Healthy', "<fg=green>{$healthy}</>");
        $this->components->twoColumnDetail('Unhealthy', "<fg=red>{$unhealthy}</>");

        if ($unhealthy > 0) {
            $this->newLine();
            $this->components->warn("⚠️  {$unhealthy} streams are experiencing issues");
        }

        return Command::SUCCESS;
    }
}
