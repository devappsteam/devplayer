<?php

namespace App\Modules\IPTV\Services;

use App\Core\Services\BaseService;
use App\Modules\IPTV\Repositories\Contracts\IPTVRepositoryInterface;
use App\Modules\IPTV\Helpers\XtreamApiClient;
use App\Modules\IPTV\Helpers\M3UParser;
use App\Modules\IPTV\Helpers\M3UStreamUrlManager;
use App\Modules\IPTV\Enums\PlaylistProvider;
use App\Modules\IPTV\Enums\IPTVStatus;
use App\Modules\IPTV\Models\IPTV;
use App\Modules\IPTV\Models\SyncProgress;
use App\Modules\IPTV\Jobs\SyncIPTVCategoriesJob;
use App\Modules\Category\Models\Category;
use App\Modules\Channel\Models\Channel;
use App\Modules\Channel\Enums\StreamType;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Exception;

class IPTVService extends BaseService
{
    public function __construct(IPTVRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Test IPTV connection
     */
    public function testConnection(array $credentials): array
    {
        try {
            $client = new XtreamApiClient(
                $credentials['url'],
                $credentials['username'],
                $credentials['password']
            );

            return $client->testConnection();
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Synchronize IPTV provider data asynchronously using Jobs
     */
    public function synchronizeAsync(IPTV $iptv, ?array $types = null): array
    {
        try {
            // Test connection first
            if ($iptv->provider_type === PlaylistProvider::XTREAM) {
                $client = new XtreamApiClient(
                    $iptv->url,
                    $iptv->username,
                    $iptv->password
                );

                $connectionTest = $client->testConnection();
                if (!$connectionTest['success']) {
                    $iptv->update(['status' => IPTVStatus::ERROR]);
                    return $connectionTest;
                }
            }

            // Generate unique sync ID for this operation
            $syncId = Str::uuid()->toString();

            // Determine which types to sync
            $typesToSync = $types ?? [StreamType::LIVE, StreamType::VOD, StreamType::SERIES];
            if (!is_array($typesToSync)) {
                $typesToSync = [$typesToSync];
            }

            // Create progress records for each type
            DB::transaction(function () use ($iptv, $syncId, $typesToSync) {
                foreach ($typesToSync as $type) {
                    $streamType = $type instanceof StreamType ? $type : StreamType::from($type);

                    SyncProgress::create([
                        'sync_id' => $syncId,
                        'iptv_id' => $iptv->id,
                        'type' => $streamType->value,
                        'status' => 'pending',
                        'total_items' => 0,
                        'processed_items' => 0,
                        'message' => 'Aguardando início...',
                        'started_at' => now(),
                    ]);
                }
            });

            // Dispatch jobs for each type
            foreach ($typesToSync as $type) {
                $streamType = $type instanceof StreamType ? $type : StreamType::from($type);
                SyncIPTVCategoriesJob::dispatch($iptv, $streamType, $syncId);
            }

            return [
                'success' => true,
                'message' => 'Synchronization started',
                'sync_id' => $syncId,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get synchronization progress
     */
    public function getSyncProgress(string $syncId): array
    {
        $progress = SyncProgress::where('sync_id', $syncId)
            ->orderBy('type')
            ->get();

        if ($progress->isEmpty()) {
            return [
                'success' => false,
                'message' => 'Sync not found',
            ];
        }

        $overallStatus = $this->calculateOverallStatus($progress);

        return [
            'success' => true,
            'sync_id' => $syncId,
            'overall_status' => $overallStatus,
            'types' => $progress->map(function ($item) {
                return [
                    'type' => $item->type,
                    'status' => $item->status,
                    'current_step' => $item->current_step,
                    'total_items' => $item->total_items,
                    'processed_items' => $item->processed_items,
                    'progress_percentage' => $item->progress_percentage,
                    'message' => $item->message,
                    'error_message' => $item->error_message,
                    'metadata' => $item->metadata,
                    'started_at' => $item->started_at,
                    'completed_at' => $item->completed_at,
                ];
            }),
        ];
    }

    /**
     * Calculate overall status from individual progress items
     */
    protected function calculateOverallStatus($progressItems): string
    {
        $statuses = $progressItems->pluck('status')->unique();

        if ($statuses->contains('failed')) {
            return 'failed';
        }

        if ($statuses->contains('processing')) {
            return 'processing';
        }

        if ($statuses->contains('pending')) {
            return 'pending';
        }

        if ($statuses->every(fn($status) => $status === 'completed')) {
            return 'completed';
        }

        return 'unknown';
    }

    /**
     * Synchronize IPTV provider data (synchronous - kept for CLI)
     */
    public function synchronize(IPTV $iptv): array
    {
        try {
            // Handle M3U based providers
            if (in_array($iptv->provider_type, [PlaylistProvider::M3U, PlaylistProvider::M3U_URL])) {
                return $this->synchronizeM3U($iptv);
            }

            // Handle Xtream provider
            if ($iptv->provider_type !== PlaylistProvider::XTREAM) {
                throw new Exception("Provider type {$iptv->provider_type->value} not supported");
            }

            $client = new XtreamApiClient(
                $iptv->url,
                $iptv->username,
                $iptv->password
            );

            // Test connection first
            $connectionTest = $client->testConnection();
            if (!$connectionTest['success']) {
                $iptv->update(['status' => IPTVStatus::ERROR]);
                return $connectionTest;
            }

            $stats = [
                'categories_created' => 0,
                'categories_updated' => 0,
                'channels_created' => 0,
                'channels_updated' => 0,
                'errors' => [],
            ];

            // Sync Live categories and channels
            $stats = $this->syncCategories($iptv, $client, $stats, StreamType::LIVE);
            $stats = $this->syncLiveChannels($iptv, $client, $stats);

            // Sync VOD categories and channels
            $stats = $this->syncCategories($iptv, $client, $stats, StreamType::VOD);
            $stats = $this->syncVodChannels($iptv, $client, $stats);

            // Sync Series categories and channels
            $stats = $this->syncCategories($iptv, $client, $stats, StreamType::SERIES);
            $stats = $this->syncSeriesChannels($iptv, $client, $stats);

            // Update IPTV status
            $iptv->update([
                'status' => IPTVStatus::ACTIVE,
                'last_sync_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => 'Synchronization completed successfully',
                'stats' => $stats,
            ];
        } catch (Exception $e) {
            $iptv->update(['status' => IPTVStatus::ERROR]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Synchronize M3U provider data
     */
    protected function synchronizeM3U(IPTV $iptv): array
    {
        try {
            $stats = [
                'categories_created' => 0,
                'categories_updated' => 0,
                'channels_created' => 0,
                'channels_updated' => 0,
                'errors' => [],
            ];

            // Parse M3U based on provider type
            if ($iptv->provider_type === PlaylistProvider::M3U_URL) {
                $parsedM3U = M3UParser::parseFromUrl($iptv->url);
            } else {
                $parsedM3U = M3UParser::parseFromFile($iptv->url);
            }

            // Store stream URLs for later retrieval
            $streamUrls = M3UParser::storeStreamUrls($parsedM3U);
            M3UStreamUrlManager::store($iptv->id, $streamUrls);

            // Sync each stream type
            foreach ($parsedM3U as $type => $categories) {
                $streamType = match($type) {
                    'live' => StreamType::LIVE,
                    'vod' => StreamType::VOD,
                    'series' => StreamType::SERIES,
                    default => StreamType::LIVE,
                };

                $stats = $this->syncM3UCategories($iptv, $categories, $stats, $streamType);
                $stats = $this->syncM3UChannels($iptv, $categories, $stats, $streamType);
            }

            // Update IPTV status
            $iptv->update([
                'status' => IPTVStatus::ACTIVE,
                'last_sync_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => 'M3U Synchronization completed successfully',
                'stats' => $stats,
            ];
        } catch (Exception $e) {
            $iptv->update(['status' => IPTVStatus::ERROR]);

            return [
                'success' => false,
                'error' => "M3U sync error: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Sync categories from M3U
     */
    protected function syncM3UCategories(IPTV $iptv, array $categories, array $stats, StreamType $streamType): array
    {
        try {
            $categoryIndex = 0;

            foreach ($categories as $categoryName => $_channels) {
                $category = Category::updateOrCreate(
                    [
                        'iptv_id' => $iptv->id,
                        'external_id' => md5($categoryName . $streamType->value),
                        'type' => $streamType->value,
                    ],
                    [
                        'name' => $categoryName,
                        'order' => $categoryIndex,
                    ]
                );

                if ($category->wasRecentlyCreated) {
                    $stats['categories_created']++;
                } else {
                    $stats['categories_updated']++;
                }

                $categoryIndex++;
            }
        } catch (Exception $e) {
            $stats['errors'][] = "M3U {$streamType->value} categories sync error: {$e->getMessage()}";
        }

        return $stats;
    }

    /**
     * Sync channels from M3U
     */
    protected function syncM3UChannels(IPTV $iptv, array $categories, array $stats, StreamType $streamType): array
    {
        try {
            $channelIndex = 1;

            foreach ($categories as $categoryName => $channels) {
                // Find or create category
                $category = Category::where('iptv_id', $iptv->id)
                    ->where('external_id', md5($categoryName . $streamType->value))
                    ->where('type', $streamType->value)
                    ->first();

                if (!$category) {
                    continue;
                }

                foreach ($channels as $channelData) {
                    $externalId = md5($channelData['name'] . $channelData['url']);

                    $channel = Channel::updateOrCreate(
                        [
                            'iptv_id' => $iptv->id,
                            'external_id' => $externalId,
                            'stream_type' => $streamType,
                        ],
                        [
                            'category_id' => $category->id,
                            'name' => $channelData['name'],
                            'logo_url' => $channelData['logo_url'] ?? null,
                            'stream_url' => $channelData['url'],
                            'epg_channel_id' => $channelData['tvg_name'] ?? null,
                            'number' => $channelIndex,
                            'is_active' => true,
                            'metadata' => [
                                'group_title' => $channelData['group_title'] ?? '',
                                'tvg_name' => $channelData['tvg_name'] ?? '',
                                'source' => 'm3u',
                                'added' => now(),
                            ],
                        ]
                    );

                    if ($channel->wasRecentlyCreated) {
                        $stats['channels_created']++;
                    } else {
                        $stats['channels_updated']++;
                    }

                    $channelIndex++;
                }
            }

            // Update categories channels count
            $this->updateCategoriesCount($iptv);
        } catch (Exception $e) {
            $stats['errors'][] = "M3U {$streamType->value} channels sync error: {$e->getMessage()}";
        }

        return $stats;
    }

    /**
     * Sync categories from Xtream
     */
    protected function syncCategories(IPTV $iptv, XtreamApiClient $client, array $stats, StreamType $type): array
    {
        try {
            $categories = match($type) {
                StreamType::LIVE => $client->getLiveCategories(),
                StreamType::VOD => $client->getVodCategories(),
                StreamType::SERIES => $client->getSeriesCategories(),
            };

            foreach ($categories as $index => $categoryData) {
                // Ensure we have a valid external_id (critical for unique constraint)
                $categoryId = $categoryData['category_id'] ?? null;
                $categoryName = $categoryData['category_name'] ?? 'Unknown';

                // If no external_id, generate one based on name + type
                if (empty($categoryId)) {
                    $categoryId = md5($categoryName . '_' . $type->value);
                }

                $category = Category::updateOrCreate(
                    [
                        'iptv_id' => $iptv->id,
                        'external_id' => $categoryId,
                        'type' => $type->value,
                    ],
                    [
                        'name' => $categoryName,
                        'order' => $index,
                    ]
                );

                if ($category->wasRecentlyCreated) {
                    $stats['categories_created']++;
                } else {
                    $stats['categories_updated']++;
                }
            }
        } catch (Exception $e) {
            $stats['errors'][] = "{$type->value} categories sync error: {$e->getMessage()}";
        }

        return $stats;
    }

    /**
     * Sync live channels from Xtream
     */
    protected function syncLiveChannels(IPTV $iptv, XtreamApiClient $client, array $stats): array
    {
        try {
            $streams = $client->getLiveStreams();

            foreach ($streams as $streamData) {
                // Find category
                $category = null;
                if (isset($streamData['category_id'])) {
                    $category = Category::where('iptv_id', $iptv->id)
                        ->where('external_id', $streamData['category_id'])
                        ->first();
                }

                $streamId = $streamData['stream_id'] ?? $streamData['num'] ?? null;

                if (!$streamId) {
                    continue;
                }

                $streamUrl = $client->getStreamUrl($streamId, 'm3u8');

                $channel = Channel::updateOrCreate(
                    [
                        'iptv_id' => $iptv->id,
                        'external_id' => $streamId,
                    ],
                    [
                        'category_id' => $category?->id,
                        'name' => $streamData['name'] ?? 'Unknown Channel',
                        'logo_url' => $streamData['stream_icon'] ?? null,
                        'stream_url' => $streamUrl,
                        'stream_type' => StreamType::LIVE,
                        'epg_channel_id' => $streamData['epg_channel_id'] ?? null,
                        'number' => $streamData['num'] ?? null,
                        'is_active' => true,
                        'metadata' => [
                            'added' => $streamData['added'] ?? null,
                            'rating' => $streamData['rating'] ?? null,
                            'rating_5based' => $streamData['rating_5based'] ?? null,
                        ],
                    ]
                );

                if ($channel->wasRecentlyCreated) {
                    $stats['channels_created']++;
                } else {
                    $stats['channels_updated']++;
                }
            }

            // Update categories channels count
            $this->updateCategoriesCount($iptv);
        } catch (Exception $e) {
            $stats['errors'][] = "Channels sync error: {$e->getMessage()}";
        }

        return $stats;
    }

    /**
     * Sync VOD channels from Xtream
     */
    protected function syncVodChannels(IPTV $iptv, XtreamApiClient $client, array $stats): array
    {
        try {
            $streams = $client->getVodStreams();

            foreach ($streams as $streamData) {
                // Find category
                $category = null;
                if (isset($streamData['category_id'])) {
                    $category = Category::where('iptv_id', $iptv->id)
                        ->where('external_id', $streamData['category_id'])
                        ->where('type', StreamType::VOD->value)
                        ->first();
                }

                $streamId = $streamData['stream_id'] ?? $streamData['num'] ?? null;

                if (!$streamId) {
                    continue;
                }

                $streamUrl = $client->getVodStreamUrl($streamId);

                $channel = Channel::updateOrCreate(
                    [
                        'iptv_id' => $iptv->id,
                        'external_id' => $streamId,
                        'stream_type' => StreamType::VOD,
                    ],
                    [
                        'category_id' => $category?->id,
                        'name' => $streamData['name'] ?? 'Unknown Movie',
                        'logo_url' => $streamData['stream_icon'] ?? $streamData['cover_big'] ?? null,
                        'stream_url' => $streamUrl,
                        'epg_channel_id' => null,
                        'number' => $streamData['num'] ?? null,
                        'is_active' => true,
                        'metadata' => [
                            'container_extension' => $streamData['container_extension'] ?? null,
                            'added' => $streamData['added'] ?? null,
                            'rating' => $streamData['rating'] ?? null,
                            'rating_5based' => $streamData['rating_5based'] ?? null,
                            'plot' => $streamData['plot'] ?? null,
                            'cast' => $streamData['cast'] ?? null,
                            'director' => $streamData['director'] ?? null,
                            'genre' => $streamData['genre'] ?? null,
                            'release_date' => $streamData['releasedate'] ?? null,
                            'duration' => $streamData['duration'] ?? null,
                            'youtube_trailer' => $streamData['youtube_trailer'] ?? null,
                        ],
                    ]
                );

                if ($channel->wasRecentlyCreated) {
                    $stats['channels_created']++;
                } else {
                    $stats['channels_updated']++;
                }
            }

            // Update categories channels count
            $this->updateCategoriesCount($iptv);
        } catch (Exception $e) {
            $stats['errors'][] = "VOD sync error: {$e->getMessage()}";
        }

        return $stats;
    }

    /**
     * Sync series from Xtream
     */
    protected function syncSeriesChannels(IPTV $iptv, XtreamApiClient $client, array $stats): array
    {
        try {
            $series = $client->getSeries();

            foreach ($series as $seriesData) {
                // Find category
                $category = null;
                if (isset($seriesData['category_id'])) {
                    $category = Category::where('iptv_id', $iptv->id)
                        ->where('external_id', $seriesData['category_id'])
                        ->where('type', StreamType::SERIES->value)
                        ->first();
                }

                $seriesId = $seriesData['series_id'] ?? $seriesData['num'] ?? null;

                if (!$seriesId) {
                    continue;
                }

                // For series, we store the series info, episodes can be fetched later
                $channel = Channel::updateOrCreate(
                    [
                        'iptv_id' => $iptv->id,
                        'external_id' => $seriesId,
                        'stream_type' => StreamType::SERIES,
                    ],
                    [
                        'category_id' => $category?->id,
                        'name' => $seriesData['name'] ?? 'Unknown Series',
                        'logo_url' => $this->extractImageUrl($seriesData['backdrop_path']),
                        'stream_url' => null, // Series don't have a single stream URL
                        'epg_channel_id' => null,
                        'number' => $seriesData['num'] ?? null,
                        'is_active' => true,
                        'metadata' => [
                            'last_modified' => $seriesData['last_modified'] ?? null,
                            'rating' => $seriesData['rating'] ?? null,
                            'rating_5based' => $seriesData['rating_5based'] ?? null,
                            'plot' => $seriesData['plot'] ?? null,
                            'cast' => $seriesData['cast'] ?? null,
                            'director' => $seriesData['director'] ?? null,
                            'genre' => $seriesData['genre'] ?? null,
                            'release_date' => $seriesData['releaseDate'] ?? null,
                            'youtube_trailer' => $seriesData['youtube_trailer'] ?? null,
                            'episode_run_time' => $seriesData['episode_run_time'] ?? null,
                            'backdrop_path' => $this->extractImageUrl($seriesData['backdrop_path']),
                        ],
                    ]
                );

                if ($channel->wasRecentlyCreated) {
                    $stats['channels_created']++;
                } else {
                    $stats['channels_updated']++;
                }
            }

            // Update categories channels count
            $this->updateCategoriesCount($iptv);
        } catch (Exception $e) {
            $stats['errors'][] = "Series sync error: {$e->getMessage()}";
        }

        return $stats;
    }

    /**
     * Update channels count for all categories
     */
    protected function updateCategoriesCount(IPTV $iptv): void
    {
        $categories = Category::where('iptv_id', $iptv->id)->get();

        foreach ($categories as $category) {
            $count = Channel::where('category_id', $category->id)
                ->where('is_active', true)
                ->count();

            $category->update(['channels_count' => $count]);
        }
    }

    /**
     * Get IPTV with statistics
     */
    public function getWithStats(string $uuid): ?array
    {
        $iptv = $this->findByUuid($uuid, ['categories', 'channels', 'playlists']);

        if (!$iptv) {
            return null;
        }

        return [
            'iptv' => $iptv,
            'stats' => [
                'categories_count' => $iptv->categories->count(),
                'channels_count' => $iptv->channels->count(),
                'active_channels_count' => $iptv->channels()->where('is_active', true)->count(),
                'playlists_count' => $iptv->playlists->count(),
                'last_sync' => $iptv->last_sync_at?->diffForHumans(),
            ],
        ];
    }

    /**
     * Get active IPTVs for user
     */
    public function getActiveForUser(int $userId)
    {
        return IPTV::where('user_id', $userId)
            ->where('status', IPTVStatus::ACTIVE)
            ->with(['categories', 'channels'])
            ->get();
    }

    /**
     * Extract image URL from backdrop_path (can be array or string)
     * Xtreamcode returns backdrop_path as array with image URLs
     */
    protected function extractImageUrl($imageData): ?string
    {
        if (is_null($imageData)) {
            return null;
        }

        // If it's an array, get the first URL
        if (is_array($imageData) && !empty($imageData)) {
            return $imageData[0];
        }

        // If it's already a string, return it
        if (is_string($imageData) && !empty($imageData)) {
            return $imageData;
        }

        return null;
    }
}
