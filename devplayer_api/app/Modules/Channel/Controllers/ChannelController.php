<?php

namespace App\Modules\Channel\Controllers;

use App\Http\Controllers\Controller;
use App\Core\Traits\ApiResponse;
use App\Modules\Channel\Services\ChannelService;
use App\Modules\Channel\Services\EpisodeService;
use App\Modules\Channel\Requests\StoreChannelRequest;
use App\Modules\Channel\Requests\UpdateChannelRequest;
use App\Modules\Channel\Resources\ChannelResource;
use App\Modules\Channel\Resources\ChannelDetailResource;
use App\Modules\Channel\Resources\EpisodeResource;
use App\Modules\Channel\Enums\StreamType;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ChannelController extends Controller
{
    use ApiResponse;

    protected ChannelService $service;
    protected EpisodeService $episodeService;

    public function __construct(ChannelService $service, EpisodeService $episodeService)
    {
        $this->service = $service;
        $this->episodeService = $episodeService;
    }

    public function index(): JsonResponse
    {
        // Check if filtering by category UUID
        if (request()->has('category_uuid')) {
            return $this->byCategoryUuid();
        }

        $channels = $this->service->paginate(
            perPage: request('per_page', 15)
        );

        return $this->paginatedResponse(
            $channels,
            'Channels retrieved successfully'
        );
    }

    public function store(StoreChannelRequest $request): JsonResponse
    {
        $channel = $this->service->create($request->validated());

        return $this->createdResponse(
            new ChannelResource($channel),
            'Channel created successfully'
        );
    }

    public function show(string $uuid): JsonResponse
    {
        $channel = $this->service->findByUuid($uuid);

        if (!$channel) {
            return $this->notFoundResponse('Channel not found');
        }

        $response = new ChannelDetailResource($channel);

        // Se for uma série, incluir episodes agrupados por temporada
        if ($channel->stream_type === StreamType::SERIES) {
            $episodes = $this->episodeService->getGroupedByChannelId($channel->id);

            $responseData = $response->resolve();
            $responseData['seasons'] = collect($episodes)
                ->map(function ($seasonEpisodes, $seasonNumber) {
                    return [
                        'season' => (int) $seasonNumber,
                        'episodes' => collect($seasonEpisodes)->map(function ($episode) {
                            return [
                                'id' => $episode['id'],
                                'uuid' => $episode['uuid'],
                                'season' => $episode['season'],
                                'episode' => $episode['episode'],
                                'name' => $episode['name'],
                                'full_title' => sprintf('S%02dE%02d - %s', $episode['season'], $episode['episode'], $episode['name']),
                                'description' => $episode['description'],
                                'plot' => $episode['plot'],
                                'aired_date' => $episode['aired_date'],
                                'duration' => $episode['duration'],
                                'thumbnail_url' => $episode['thumbnail_url'],
                                'stream_url' => $episode['metadata']['stream_url'] ?? null,
                                'is_active' => $episode['is_active'],
                            ];
                        })->toArray(),
                    ];
                })
                ->values()
                ->toArray();

            return $this->successResponse(
                $responseData,
                'Channel details retrieved successfully'
            );
        }

        return $this->successResponse(
            $response,
            'Channel details retrieved successfully'
        );
    }

    public function update(UpdateChannelRequest $request, string $uuid): JsonResponse
    {
        $channel = $this->service->findByUuid($uuid);

        if (!$channel) {
            return $this->notFoundResponse('Channel not found');
        }

        $updated = $this->service->update($channel, $request->validated());

        if (!$updated) {
            return $this->serverErrorResponse('Failed to update channel');
        }

        return $this->successResponse(
            new ChannelResource($channel->fresh()),
            'Channel updated successfully'
        );
    }

    public function destroy(string $uuid): JsonResponse
    {
        $channel = $this->service->findByUuid($uuid);

        if (!$channel) {
            return $this->notFoundResponse('Channel not found');
        }

        $deleted = $this->service->delete($channel);

        if (!$deleted) {
            return $this->serverErrorResponse('Failed to delete channel');
        }

        return $this->successResponse(
            null,
            'Channel deleted successfully'
        );
    }

    /**
     * Search channels
     */
    public function search(): JsonResponse
    {
        $query = request('q', '');
        $filters = request()->only(['category_id', 'iptv_id', 'stream_type', 'has_epg', 'sort_by', 'sort_order', 'per_page']);

        $channels = $this->service->search($query, $filters);

        return $this->paginatedResponse(
            $channels,
            'Search results retrieved successfully'
        );
    }

    /**
     * Get live channels
     */
    public function live(): JsonResponse
    {
        $channels = $this->service->getLiveChannels(request('per_page', 20));

        return $this->paginatedResponse(
            $channels,
            'Live channels retrieved successfully'
        );
    }

    /**
     * Get movies (VOD)
     */
    public function movies(): JsonResponse
    {
        $channels = $this->service->getByType(\App\Modules\Channel\Enums\StreamType::VOD, request('per_page', 20));

        return $this->paginatedResponse(
            $channels,
            'Movies retrieved successfully'
        );
    }

    /**
     * Get series
     */
    public function series(): JsonResponse
    {
        $channels = $this->service->getByType(\App\Modules\Channel\Enums\StreamType::SERIES, request('per_page', 20));

        return $this->paginatedResponse(
            $channels,
            'Series retrieved successfully'
        );
    }

    /**
     * Get channels by type
     */
    public function byType(string $type): JsonResponse
    {
        try {
            $streamType = \App\Modules\Channel\Enums\StreamType::from($type);
            $channels = $this->service->getByType($streamType, request('per_page', 20));

            return $this->paginatedResponse(
                $channels,
                "Channels of type '{$type}' retrieved successfully"
            );
        } catch (\ValueError $e) {
            return $this->validationErrorResponse(['type' => 'Invalid stream type']);
        }
    }

    /**
     * Get channels by category
     */
    public function byCategory(int $categoryId): JsonResponse
    {
        $channels = $this->service->getByCategory($categoryId, request('per_page', 20));

        return $this->paginatedResponse(
            $channels,
            'Category channels retrieved successfully'
        );
    }

    /**
     * Get channels by category UUID (query parameter)
     */
    public function byCategoryUuid(): JsonResponse
    {
        $categoryUuid = request('category_uuid');

        if (!$categoryUuid) {
            return $this->errorResponse('Category UUID is required', 400);
        }

        $channels = $this->service->getByCategoryUuid($categoryUuid, request('per_page', 20));

        return $this->paginatedResponse(
            $channels,
            'Category channels retrieved successfully'
        );
    }

    /**
     * Toggle favorite
     */
    public function toggleFavorite(string $uuid): JsonResponse
    {
        $userId = Auth::id();

        if (!$userId) {
            return $this->unauthorizedResponse('User not authenticated');
        }

        $favorited = $this->service->toggleFavorite($userId, $uuid);

        return $this->successResponse(
            ['is_favorited' => $favorited],
            $favorited ? 'Channel added to favorites' : 'Channel removed from favorites'
        );
    }

    /**
     * Get channel EPG
     */
    public function epg(string $uuid): JsonResponse
    {
        $date = request('date');
        $epgData = $this->service->getEpg($uuid, $date);

        return $this->successResponse(
            $epgData,
            'EPG data retrieved successfully'
        );
    }

    /**
     * Get channel statistics
     */
    public function statistics(string $uuid): JsonResponse
    {
        $stats = $this->service->getStatistics($uuid);

        return $this->successResponse(
            $stats,
            'Channel statistics retrieved successfully'
        );
    }

    /**
     * Get stream URL for channel
     */
    public function getStream(string $uuid): JsonResponse
    {
        $channel = $this->service->findByUuid($uuid);

        if (!$channel) {
            return $this->notFoundResponse('Channel not found');
        }

        $quality = request('quality');
        $streamQuality = null;

        if ($quality) {
            try {
                $streamQuality = \App\Modules\Stream\Enums\StreamQuality::from($quality);
            } catch (\ValueError $e) {
                return $this->validationErrorResponse(['quality' => 'Invalid quality value']);
            }
        }

        $streamService = app(\App\Modules\Stream\Services\StreamService::class);
        $streamUrl = $streamService->getStreamUrl($channel, $streamQuality);

        if (!$streamUrl) {
            return $this->notFoundResponse('No stream available for this channel');
        }

        return $this->successResponse(
            ['stream_url' => $streamUrl],
            'Stream URL retrieved successfully'
        );
    }

    /**
     * Get VOD (Movie) information from Xtreamcode provider
     */
    public function vodInfo(string $uuid): JsonResponse
    {
        $channel = $this->service->findByUuid($uuid);

        if (!$channel) {
            return $this->notFoundResponse('Channel not found');
        }

        if (!$channel->external_id) {
            return $this->errorResponse('Channel has no external ID', 400);
        }

        if (!$channel->iptv) {
            return $this->errorResponse('Channel has no IPTV provider configured', 400);
        }

        try {
            $xtreamcodeService = new \App\Modules\IPTV\Services\XtreamcodeService($channel->iptv);
            $vodInfo = $xtreamcodeService->getVodInfo($channel->external_id);

            if (!$vodInfo) {
                return $this->notFoundResponse('VOD information not found on provider');
            }

            return $this->successResponse(
                $vodInfo,
                'VOD information retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Error fetching VOD information: ' . $e->getMessage());
        }
    }

    /**
     * Get Series information from Xtreamcode provider
     * Caches episodes in database after first fetch
     */
    public function seriesInfo(string $uuid): JsonResponse
    {
        $channel = $this->service->findByUuid($uuid);

        if (!$channel) {
            return $this->notFoundResponse('Channel not found');
        }

        // Check if we have episodes cached in database
        $episodesCount = $this->episodeService->countByChannelId($channel->id);

        // If we have episodes in DB and metadata is recent (less than 7 days), return from DB
        $lastSynced = $channel->metadata['last_synced_at'] ?? null;
        $isCacheValid = $lastSynced &&
                       \Carbon\Carbon::parse($lastSynced)->isAfter(now()->subDays(7));

        if ($episodesCount > 0 && $isCacheValid) {
            // Return from database
            return $this->seriesInfoFromDatabase($channel);
        }

        // Otherwise, fetch from IPTV provider and cache
        if (!$channel->external_id) {
            return $this->errorResponse('Channel has no external ID', 400);
        }

        if (!$channel->iptv) {
            return $this->errorResponse('Channel has no IPTV provider configured', 400);
        }

        try {
            $xtreamcodeService = new \App\Modules\IPTV\Services\XtreamcodeService($channel->iptv);
            $seriesInfo = $xtreamcodeService->getSeriesInfo($channel->external_id);

            if (!$seriesInfo) {
                return $this->notFoundResponse('Series information not found on provider');
            }

            // Get fallback image from current logo_url (what's displayed in list)
            $fallbackImageUrl = $channel->logo_url;

            // Cache series metadata and episodes in database
            $this->cacheSeriesInDatabase($channel, $seriesInfo, $fallbackImageUrl);

            return $this->successResponse(
                $seriesInfo,
                'Series information retrieved successfully'
            );
        } catch (\Exception $e) {
            // If fetch fails but we have cached data, return it anyway
            if ($episodesCount > 0) {
                return $this->seriesInfoFromDatabase($channel);
            }

            return $this->serverErrorResponse('Error fetching Series information: ' . $e->getMessage());
        }
    }

    /**
     * Get series info from database cache
     */
    protected function seriesInfoFromDatabase(\App\Modules\Channel\Models\Channel $channel): JsonResponse
    {
        $episodes = $this->episodeService->getGroupedByChannelId($channel->id);

        $seasons = collect($episodes)
            ->map(function ($seasonEpisodes, $seasonNumber) {
                return [
                    'season' => (int) $seasonNumber,
                    'episodes' => collect($seasonEpisodes)->map(function ($episode) {
                        return [
                            'id' => $episode['external_id'] ?? $episode['id'],
                            'episode_id' => $episode['external_id'] ?? $episode['id'],
                            'season' => $episode['season'],
                            'episode' => $episode['episode'],
                            'title' => $episode['name'],
                            'name' => $episode['name'],
                            'description' => $episode['description'],
                            'plot' => $episode['plot'],
                            'duration' => $episode['duration'],
                            'aired_date' => $episode['aired_date'],
                            'thumbnail' => $episode['thumbnail_url'],
                            'stream_url' => $episode['metadata']['stream_url'] ?? null,
                            'rating' => $episode['metadata']['rating'] ?? null,
                        ];
                    })->values()->toArray(),
                ];
            })
            ->values()
            ->toArray();

        $metadata = $channel->metadata ?? [];

        return $this->successResponse([
            'id' => $channel->external_id,
            'name' => $channel->name,
            'description' => $metadata['description'] ?? null,
            'plot' => $metadata['plot'] ?? null,
            'rating' => $metadata['rating'] ?? null,
            'release_date' => $metadata['release_date'] ?? null,
            'genre' => $metadata['genre'] ?? null,
            'director' => $metadata['director'] ?? null,
            'cast' => $metadata['cast'] ?? null,
            'cover' => $metadata['images']['cover'] ?? $channel->logo_url,
            'poster' => $metadata['images']['poster'] ?? null,
            'backdrop' => $metadata['images']['backdrop'] ?? null,
            'backdrop_path' => $metadata['images']['backdrop'] ?? null,
            'seasons' => $seasons,
            'episodes_count' => $metadata['episodes_count'] ?? count($seasons),
            'source' => 'database',
        ], 'Series information retrieved from cache');
    }

    /**
     * Cache series data in database
     */
    protected function cacheSeriesInDatabase(
        \App\Modules\Channel\Models\Channel $channel,
        array $seriesInfo,
        ?string $fallbackImageUrl = null
    ): void {
        // Update channel metadata
        $this->service->updateSeriesMetadata($channel, $seriesInfo, $fallbackImageUrl);

        // Sync episodes
        if (isset($seriesInfo['seasons']) && is_array($seriesInfo['seasons'])) {
            $this->episodeService->syncEpisodesFromSeriesData(
                $channel->id,
                $seriesInfo['seasons']
            );
        }
    }
}

