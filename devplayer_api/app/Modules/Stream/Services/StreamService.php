<?php

namespace App\Modules\Stream\Services;

use App\Core\Services\BaseService;
use App\Modules\Stream\Repositories\Contracts\StreamRepositoryInterface;
use App\Modules\Stream\Models\Stream;
use App\Modules\Channel\Models\Channel;
use App\Modules\Stream\Enums\StreamQuality;
use App\Modules\Stream\Enums\StreamProtocol;
use App\Modules\Stream\Enums\ConnectionStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Exception;

class StreamService extends BaseService
{
    protected int $healthCheckTimeout = 5;
    protected int $maxRetries = 3;

    public function __construct(StreamRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Resolve the best stream for a channel based on quality preference
     */
    public function resolveStream(Channel $channel, ?StreamQuality $preferredQuality = null): ?Stream
    {
        $preferredQuality = $preferredQuality ?? StreamQuality::AUTO;

        // Get all healthy streams for the channel, prioritizing primary streams
        $streams = $channel->streams()
            ->healthy()
            ->orderBy('is_primary', 'desc')
            ->orderBy('quality', 'desc')
            ->get();

        if ($streams->isEmpty()) {
            // No healthy streams, try to find any stream and check its health
            $anyStream = $channel->streams()->first();
            if ($anyStream && $this->checkHealth($anyStream)) {
                return $anyStream;
            }
            return null;
        }

        // If AUTO, return the best available (highest quality that's healthy)
        if ($preferredQuality === StreamQuality::AUTO) {
            return $streams->first();
        }

        // Try to find exact quality match
        $exactMatch = $streams->firstWhere('quality', $preferredQuality);
        if ($exactMatch) {
            return $exactMatch;
        }

        // Fallback to closest quality
        return $this->findClosestQuality($streams, $preferredQuality);
    }

    /**
     * Find the closest quality match from available streams
     */
    protected function findClosestQuality($streams, StreamQuality $targetQuality): ?Stream
    {
        $targetHeight = $targetQuality->height();

        $closest = $streams->sortBy(function ($stream) use ($targetHeight) {
            return abs($stream->quality->height() - $targetHeight);
        })->first();

        return $closest;
    }

    /**
     * Check stream health and update metrics
     */
    public function checkHealth(Stream $stream): bool
    {
        if (!$stream->needsHealthCheck()) {
            return $stream->isHealthy();
        }

        try {
            $startTime = microtime(true);

            $response = Http::timeout($this->healthCheckTimeout)
                ->head($stream->url);

            $latency = (microtime(true) - $startTime) * 1000; // Convert to ms

            if ($response->successful()) {
                $status = $this->determineConnectionStatus($latency);
                $this->updateHealthMetrics($stream, $latency, $status);
                return true;
            }

            $this->markAsUnhealthy($stream);
            return false;

        } catch (Exception $e) {
            $this->markAsUnhealthy($stream);
            return false;
        }
    }

    /**
     * Determine connection status based on latency
     */
    protected function determineConnectionStatus(float $latency): ConnectionStatus
    {
        if ($latency < 100) {
            return ConnectionStatus::EXCELLENT;
        } elseif ($latency < 200) {
            return ConnectionStatus::ONLINE;
        } elseif ($latency < 500) {
            return ConnectionStatus::SLOW;
        } else {
            return ConnectionStatus::UNSTABLE;
        }
    }

    /**
     * Update stream health metrics
     */
    public function updateHealthMetrics(Stream $stream, float $latency, ConnectionStatus $status): void
    {
        $stream->update([
            'health_status' => $status,
            'latency' => $latency,
            'last_check_at' => now(),
            'is_available' => true,
        ]);

        // Invalidate channel cache
        Cache::forget("channel_{$stream->channel_id}_streams");
    }

    /**
     * Mark stream as unhealthy
     */
    protected function markAsUnhealthy(Stream $stream): void
    {
        $stream->update([
            'health_status' => ConnectionStatus::OFFLINE,
            'last_check_at' => now(),
            'is_available' => false,
        ]);

        Cache::forget("channel_{$stream->channel_id}_streams");
    }

    /**
     * Get cached stream URL with health check
     */
    public function getStreamUrl(Channel $channel, ?StreamQuality $quality = null): ?string
    {
        $cacheKey = "channel_{$channel->id}_stream_url_" . ($quality?->value ?? 'auto');

        return Cache::remember($cacheKey, 300, function () use ($channel, $quality) {
            // Try to resolve from related Stream models first
            $stream = $this->resolveStream($channel, $quality);
            if ($stream?->url) {
                return $stream->url;
            }

            // Fallback to channel's direct stream_url field
            return $channel->stream_url;
        });
    }

    /**
     * Create or update stream for a channel
     */
    public function createOrUpdateStream(Channel $channel, array $data): Stream
    {
        $stream = $channel->streams()->updateOrCreate(
            [
                'url' => $data['url'],
            ],
            [
                'protocol' => $data['protocol'] ?? StreamProtocol::HLS,
                'quality' => $data['quality'] ?? StreamQuality::AUTO,
                'bitrate' => $data['bitrate'] ?? null,
                'is_primary' => $data['is_primary'] ?? false,
                'is_available' => true,
            ]
        );

        // Perform initial health check
        $this->checkHealth($stream);

        return $stream;
    }

    /**
     * Batch health check for multiple streams
     */
    public function batchHealthCheck(array $streamIds): array
    {
        $streams = Stream::whereIn('id', $streamIds)->get();
        $results = [];

        foreach ($streams as $stream) {
            $results[$stream->id] = $this->checkHealth($stream);
        }

        return $results;
    }

    /**
     * Get available qualities for a channel
     */
    public function getAvailableQualities(Channel $channel): array
    {
        return $channel->streams()
            ->healthy()
            ->pluck('quality')
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Switch stream quality (for ABR)
     */
    public function switchQuality(Channel $channel, StreamQuality $newQuality): ?Stream
    {
        $stream = $this->resolveStream($channel, $newQuality);

        if ($stream) {
            // Mark as primary for this quality level
            $channel->streams()->update(['is_primary' => false]);
            $stream->update(['is_primary' => true]);

            Cache::forget("channel_{$channel->id}_streams");
        }

        return $stream;
    }
}
