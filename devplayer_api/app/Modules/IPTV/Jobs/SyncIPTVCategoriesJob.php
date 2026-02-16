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
use App\Modules\Channel\Enums\StreamType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class SyncIPTVCategoriesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;
    public $backoff = 30; // Retry after 30 seconds

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
                'status' => 'processing',
                'current_step' => 'categories',
                'message' => "Sincronizando categorias {$this->streamType->value}...",
            ]);

            $client = new XtreamApiClient(
                $this->iptv->url,
                $this->iptv->username,
                $this->iptv->password
            );

            // Get categories based on type
            $categories = match($this->streamType) {
                StreamType::LIVE => $client->getLiveCategories(),
                StreamType::VOD => $client->getVodCategories(),
                StreamType::SERIES => $client->getSeriesCategories(),
            };

            $total = count($categories);
            $processed = 0;
            $created = 0;
            $updated = 0;

            $progress->update([
                'total_items' => $total,
                'processed_items' => 0,
            ]);

            // Process categories in chunks to avoid memory issues
            $chunks = array_chunk($categories, 50);

            foreach ($chunks as $chunk) {
                foreach ($chunk as $index => $categoryData) {
                    // Ensure we have a valid external_id (critical for unique constraint)
                    $categoryId = $categoryData['category_id'] ?? null;
                    $categoryName = $categoryData['category_name'] ?? 'Unknown';

                    // If no external_id, generate one based on name + type
                    if (empty($categoryId)) {
                        $categoryId = md5($categoryName . '_' . $this->streamType->value);
                    } else {
                        // Convert to string to ensure consistency
                        $categoryId = (string) $categoryId;
                    }

                    $category = Category::updateOrCreate(
                        [
                            'iptv_id' => $this->iptv->id,
                            'external_id' => $categoryId,
                            'type' => $this->streamType->value,
                        ],
                        [
                            'name' => $categoryName,
                            'order' => $categoryData['parent_id'] ?? $index,
                        ]
                    );

                    if ($category->wasRecentlyCreated) {
                        $created++;
                    } else {
                        $updated++;
                    }

                    $processed++;

                    // Update progress every 10 items
                    if ($processed % 10 === 0) {
                        $progress->update([
                            'processed_items' => $processed,
                            'message' => "Categorias: {$processed}/{$total}",
                        ]);
                    }
                }
            }

            // Final update
            $progress->update([
                'processed_items' => $processed,
                'message' => "Categorias concluídas: {$created} criadas, {$updated} atualizadas",
                'metadata' => array_merge($progress->metadata ?? [], [
                    'categories_created' => $created,
                    'categories_updated' => $updated,
                ]),
            ]);

            // Log category sync completion
            Log::info("Categories synced for {$this->streamType->value}", [
                'total' => $processed,
                'created' => $created,
                'updated' => $updated,
                'sync_id' => $this->syncId,
            ]);

            // Clear category cache
            Cache::forget("iptv_{$this->iptv->uuid}_{$this->streamType->value}_categories");

            // Dispatch the streams job
            SyncIPTVStreamsJob::dispatch($this->iptv, $this->streamType, $this->syncId);

        } catch (Exception $e) {
            Log::error("Error syncing {$this->streamType->value} categories: {$e->getMessage()}", [
                'iptv_id' => $this->iptv->id,
                'sync_id' => $this->syncId,
                'exception' => $e,
            ]);

            $progress->update([
                'status' => 'failed',
                'message' => "Erro ao sincronizar categorias: {$e->getMessage()}",
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
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
                'message' => 'Falha ao sincronizar categorias após ' . $this->tries . ' tentativas',
                'error_message' => $exception->getMessage(),
            ]);
        }
    }
}
