<?php

namespace App\Modules\Channel\Controllers;

use App\Http\Controllers\Controller;
use App\Core\Traits\ApiResponse;
use App\Modules\Channel\Services\EpisodeService;
use App\Modules\Channel\Services\ChannelService;
use App\Modules\Channel\Requests\StoreEpisodeRequest;
use App\Modules\Channel\Requests\UpdateEpisodeRequest;
use App\Modules\Channel\Resources\EpisodeResource;
use Illuminate\Http\JsonResponse;

class EpisodeController extends Controller
{
    use ApiResponse;

    protected EpisodeService $service;
    protected ChannelService $channelService;

    public function __construct(EpisodeService $service, ChannelService $channelService)
    {
        $this->service = $service;
        $this->channelService = $channelService;
    }

    /**
     * Get all episodes (paginated)
     */
    public function index(): JsonResponse
    {
        $episodes = $this->service->paginate(
            perPage: request('per_page', 50)
        );

        return $this->paginatedResponse(
            $episodes,
            'Episodes retrieved successfully'
        );
    }

    /**
     * Get episodes for a specific series/channel
     */
    public function byChannel(string $channelUuid): JsonResponse
    {
        // If requesting grouped by season
        if (request()->boolean('grouped')) {
            $channel = $this->channelService->findByUuid($channelUuid);

            if (!$channel) {
                return $this->notFoundResponse('Series not found');
            }

            $episodes = $this->service->getGroupedByChannelId($channel->id);

            return $this->successResponse(
                [
                    'channel_uuid' => $channelUuid,
                    'seasons' => collect($episodes)->map(function ($seasonEpisodes, $seasonNumber) {
                        return [
                            'season' => (int) $seasonNumber,
                            'episodes' => EpisodeResource::collection($seasonEpisodes),
                        ];
                    })->values(),
                ],
                'Episodes retrieved successfully'
            );
        }

        // Standard pagination
        $episodes = $this->service->getByChannelUuid(
            $channelUuid,
            request('per_page', 50)
        );

        return $this->paginatedResponse(
            $episodes,
            'Episodes retrieved successfully'
        );
    }

    /**
     * Get specific episode
     */
    public function show(string $uuid): JsonResponse
    {
        $episode = $this->service->findByUuid($uuid);

        if (!$episode) {
            return $this->notFoundResponse('Episode not found');
        }

        return $this->successResponse(
            new EpisodeResource($episode),
            'Episode retrieved successfully'
        );
    }

    /**
     * Store new episode
     */
    public function store(StoreEpisodeRequest $request): JsonResponse
    {
        $episode = $this->service->create($request->validated());

        return $this->createdResponse(
            new EpisodeResource($episode),
            'Episode created successfully'
        );
    }

    /**
     * Update episode
     */
    public function update(UpdateEpisodeRequest $request, string $uuid): JsonResponse
    {
        $episode = $this->service->findByUuid($uuid);

        if (!$episode) {
            return $this->notFoundResponse('Episode not found');
        }

        $updated = $this->service->update($episode, $request->validated());

        if (!$updated) {
            return $this->serverErrorResponse('Failed to update episode');
        }

        return $this->successResponse(
            new EpisodeResource($episode->fresh()),
            'Episode updated successfully'
        );
    }

    /**
     * Delete episode
     */
    public function destroy(string $uuid): JsonResponse
    {
        $episode = $this->service->findByUuid($uuid);

        if (!$episode) {
            return $this->notFoundResponse('Episode not found');
        }

        $deleted = $this->service->delete($episode);

        if (!$deleted) {
            return $this->serverErrorResponse('Failed to delete episode');
        }

        return $this->successResponse(
            null,
            'Episode deleted successfully'
        );
    }
}
