<?php

namespace App\Modules\IPTV\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Modules\IPTV\Models\IPTV;
use App\Modules\IPTV\Models\SyncProgress;
use App\Modules\IPTV\Helpers\XtreamApiClient;
use App\Modules\Category\Models\Category;
use App\Modules\Channel\Models\Channel;
use App\Modules\Channel\Enums\StreamType;
use App\Modules\Stream\Models\Stream;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Exception;

class SyncIPTVStreamsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes for large datasets
    public $tries = 3;
    public $backoff = 60; // Retry after 60 seconds

    protected IPTV $iptv;
    protected StreamType $streamType;
    protected string $syncId;

    public function __construct(IPTV $iptv, StreamType $streamType, string $syncId)
    {
        $this->iptv = $iptv;
        $this->streamType = $streamType;
        $this->syncId = $syncId;
    }

    public function handle(): void
    {
        try {
            $progress = SyncProgress::where('sync_id', $this->syncId)
                ->where('type', $this->streamType->value)
                ->first();

            if (!$progress) {
                Log::error("SyncProgress not found for sync_id: {$this->syncId}, type: {$this->streamType->value}");
                return;
            }

            $progress->update([
                'current_step' => 'streams',
                'message' => "Sincronizando conteúdo {$this->streamType->value}...",
            ]);

            $client = new XtreamApiClient(
                $this->iptv->url,
                $this->iptv->username,
                $this->iptv->password
            );

            // Get streams based on type
            $streams = match($this->streamType) {
                StreamType::LIVE => $client->getLiveStreams(),
                StreamType::VOD => $client->getVodStreams(),
                StreamType::SERIES => $client->getSeries(),
            };

            $total = count($streams);
            $processed = 0;
            $created = 0;
            $updated = 0;
            $withCategory = 0;
            $withoutCategory = 0;

            $progress->update([
                'total_items' => $total,
                'processed_items' => 0,
            ]);

            // Process streams in chunks to avoid memory issues
            $chunks = array_chunk($streams, 100);

            foreach ($chunks as $chunk) {
                // Get existing channels for this chunk to reuse UUIDs (performance optimization)
                $externalIds = array_filter(array_map(function($streamData) {
                    return $this->getStreamId($streamData);
                }, $chunk));

                $existingChannels = Channel::where('iptv_id', $this->iptv->id)
                    ->where('stream_type', $this->streamType->value)
                    ->whereIn('external_id', $externalIds)
                    ->get()
                    ->keyBy('external_id');

                // Get categories for this chunk (performance optimization)
                $categoryExternalIds = array_filter(array_map(function($streamData) {
                    // Convert to string to match database type
                    return isset($streamData['category_id']) ? (string) $streamData['category_id'] : null;
                }, $chunk));

                $categories = Category::where('iptv_id', $this->iptv->id)
                    ->where('type', $this->streamType->value)
                    ->whereIn('external_id', $categoryExternalIds)
                    ->get()
                    ->keyBy('external_id');

                // Log category matching for debugging
                if (!empty($categoryExternalIds) && $categories->isEmpty()) {
                    Log::warning("No categories found for streams", [
                        'iptv_id' => $this->iptv->id,
                        'type' => $this->streamType->value,
                        'requested_ids' => array_values($categoryExternalIds),
                    ]);
                }

                $dataToUpsert = [];

                foreach ($chunk as $streamData) {
                    // Find category from pre-loaded collection
                    // Convert category_id to string to match database external_id type
                    $category = null;
                    if (isset($streamData['category_id'])) {
                        $categoryKey = (string) $streamData['category_id'];
                        if ($categories->has($categoryKey)) {
                            $category = $categories->get($categoryKey);
                            $withCategory++;
                        } else {
                            $withoutCategory++;
                        }
                    } else {
                        $withoutCategory++;
                    }

                    $streamId = $this->getStreamId($streamData);
                    if (!$streamId) {
                        continue;
                    }

                    $externalId = $streamId;
                    $streamUrl = $this->buildStreamUrl($streamData, $streamId);

                    // Use existing UUID or generate new one
                    $uuid = $existingChannels->has($externalId)
                        ? $existingChannels->get($externalId)->uuid
                        : (string) Str::uuid();

                    $dataToUpsert[] = [
                        'uuid' => $uuid,
                        'iptv_id' => $this->iptv->id,
                        'category_id' => $category?->id,
                        'external_id' => $externalId,
                        'stream_type' => $this->streamType->value,
                        'name' => $streamData['name'] ?? 'Unknown',
                        'logo_url' => $this->extractLogoUrl($streamData),
                        'stream_url' => $streamUrl,
                        'epg_channel_id' => $streamData['epg_channel_id'] ?? null,
                        'number' => $streamData['num'] ?? null,
                        'is_active' => true,
                        'metadata' => json_encode([
                            'rating' => $streamData['rating'] ?? null,
                            'rating_5based' => $streamData['rating_5based'] ?? null,
                            'added' => $streamData['added'] ?? null,
                            'category_name' => $streamData['category_name'] ?? null,
                        ]),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $processed++;

                    // Update progress every 50 items
                    if ($processed % 50 === 0) {
                        $progress->update([
                            'processed_items' => $processed,
                            'message' => "Conteúdo: {$processed}/{$total}",
                        ]);
                    }
                }

                // Batch upsert for performance
                if (!empty($dataToUpsert)) {
                    Channel::upsert(
                        $dataToUpsert,
                        ['iptv_id', 'external_id', 'stream_type'],
                        [
                            'uuid',
                            'category_id',
                            'name',
                            'logo_url',
                            'stream_url',
                            'epg_channel_id',
                            'number',
                            'is_active',
                            'metadata',
                            'updated_at',
                        ]
                    );
                }
            }

            // Update category counts
            $this->updateCategoryCounts();

            // Mark as completed
            $progress->update([
                'status' => 'completed',
                'processed_items' => $processed,
                'completed_at' => now(),
                'message' => "Sincronização concluída: {$processed} itens processados",
                'metadata' => array_merge($progress->metadata ?? [], [
                    'streams_processed' => $processed,
                    'with_category' => $withCategory,
                    'without_category' => $withoutCategory,
                ]),
            ]);

            // Log category association stats
            Log::info("Sync completed for {$this->streamType->value}", [
                'total' => $processed,
                'with_category' => $withCategory,
                'without_category' => $withoutCategory,
                'sync_id' => $this->syncId,
            ]);

            // Clear cache
            Cache::forget("iptv_{$this->iptv->uuid}_{$this->streamType->value}_streams");

        } catch (Exception $e) {
            Log::error("Error syncing {$this->streamType->value} streams: {$e->getMessage()}", [
                'iptv_id' => $this->iptv->id,
                'sync_id' => $this->syncId,
                'exception' => $e,
            ]);

            $progress->update([
                'status' => 'failed',
                'message' => "Erro ao sincronizar conteúdo: {$e->getMessage()}",
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function getStreamId(array $streamData): ?string
    {
        return $streamData['stream_id'] ?? $streamData['series_id'] ?? $streamData['num'] ?? null;
    }

    protected function buildStreamUrl(array $streamData, string $streamId): string
    {
        $baseUrl = rtrim($this->iptv->url, '/');
        $username = $this->iptv->username;
        $password = $this->iptv->password;

        return match($this->streamType) {
            StreamType::LIVE => "{$baseUrl}/live/{$username}/{$password}/{$streamId}.m3u8",
            StreamType::VOD => "{$baseUrl}/movie/{$username}/{$password}/{$streamId}.mp4",
            StreamType::SERIES => "{$baseUrl}/series/{$username}/{$password}/{$streamId}.mp4",
        };
    }

    protected function updateCategoryCounts(): void
    {
        $categories = Category::where('iptv_id', $this->iptv->id)
            ->where('type', $this->streamType->value)
            ->get();

        foreach ($categories as $category) {
            $count = Channel::where('category_id', $category->id)
                ->where('is_active', true)
                ->count();

            $category->update(['channels_count' => $count]);
        }
    }

    public function failed(Exception $exception): void
    {
        $progress = SyncProgress::where('sync_id', $this->syncId)
            ->where('type', $this->streamType->value)
            ->first();

        if ($progress) {
            $progress->update([
                'status' => 'failed',
                'message' => 'Falha ao sincronizar conteúdo após ' . $this->tries . ' tentativas',
                'error_message' => $exception->getMessage(),
            ]);
        }
    }

    protected function extractLogoUrl(array $streamData): ?string
    {
        if ($this->streamType == StreamType::SERIES) {
            return $this->extractImageUrl($streamData['backdrop_path'] ?? $streamData['cover'] ?? null );
        }
        return $streamData['stream_icon'] ?? null;
    }

    protected function extractImageUrl(mixed $imageData = null): string
    {
        $fallback = 'https://placehold.co/1000x1000/020617/FFFFFF?text=Sem+Imagem';

        if (is_null($imageData)) {
            return $fallback;
        }

        // Se for array, pega o primeiro elemento válido
        if (is_array($imageData) && !empty($imageData)) {
            $imageData = $imageData[0];
        }

        // Se não for string válida
        if (!is_string($imageData) || empty(trim($imageData))) {
            return $fallback;
        }

        $imageData = trim($imageData);

        // Se passar de 250 caracteres
        if (mb_strlen($imageData) > 250) {
            return $fallback;
        }

        return $imageData;
    }

}
