<?php

namespace App\Modules\Stream\Observers;

use App\Modules\Stream\Models\Stream;
use App\Modules\Stream\Enums\ConnectionStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StreamObserver
{
    /**
     * Handle the Stream "created" event.
     */
    public function created(Stream $stream): void
    {
        $this->invalidateCache($stream);

        Log::info('Stream created', [
            'stream_id' => $stream->id,
            'channel_id' => $stream->channel_id,
            'quality' => $stream->quality->value,
            'protocol' => $stream->protocol->value,
        ]);
    }

    /**
     * Handle the Stream "updated" event.
     */
    public function updated(Stream $stream): void
    {
        $this->invalidateCache($stream);

        // Log health status changes
        if ($stream->isDirty('health_status')) {
            $oldStatus = $stream->getOriginal('health_status');
            $newStatus = $stream->health_status;

            Log::info('Stream health status changed', [
                'stream_id' => $stream->id,
                'channel_id' => $stream->channel_id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus->value,
                'latency' => $stream->latency,
            ]);

            // Alert if stream went offline
            if ($newStatus === ConnectionStatus::OFFLINE) {
                $this->alertStreamOffline($stream);
            }
        }
    }

    /**
     * Handle the Stream "deleted" event.
     */
    public function deleted(Stream $stream): void
    {
        $this->invalidateCache($stream);

        Log::info('Stream deleted', [
            'stream_id' => $stream->id,
            'channel_id' => $stream->channel_id,
        ]);
    }

    /**
     * Handle the Stream "restored" event.
     */
    public function restored(Stream $stream): void
    {
        $this->invalidateCache($stream);
    }

    /**
     * Handle the Stream "force deleted" event.
     */
    public function forceDeleted(Stream $stream): void
    {
        $this->invalidateCache($stream);
    }

    /**
     * Invalidate related caches
     */
    protected function invalidateCache(Stream $stream): void
    {
        Cache::forget("stream_{$stream->id}");
        Cache::forget("channel_{$stream->channel_id}_streams");
        Cache::forget("channel_{$stream->channel_id}_stream_url_auto");
        Cache::forget("channel_{$stream->channel_id}_stream_url_" . $stream->quality->value);
    }

    /**
     * Alert when stream goes offline
     */
    protected function alertStreamOffline(Stream $stream): void
    {
        // TODO: Implement notification system
        // Could send email, Slack notification, etc.

        Log::warning('Stream went offline', [
            'stream_id' => $stream->id,
            'channel_id' => $stream->channel_id,
            'channel_name' => $stream->channel?->name,
            'url' => $stream->url,
            'last_check' => $stream->last_check_at,
        ]);
    }
}
