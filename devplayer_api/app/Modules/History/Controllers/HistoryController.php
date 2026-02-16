<?php

namespace App\Modules\History\Controllers;

use App\Http\Controllers\Controller;
use App\Core\Traits\ApiResponse;
use App\Modules\History\Services\HistoryService;
use App\Modules\Channel\Resources\ChannelDetailResource;
use App\Modules\Channel\Models\Channel;
use Illuminate\Http\JsonResponse;

class HistoryController extends Controller
{
    use ApiResponse;

    protected HistoryService $service;

    public function __construct(HistoryService $service)
    {
        $this->service = $service;
    }

    public function last(string $userId, ?string $contentType = null): JsonResponse
    {
        $lastWatched = $this->service->getLastWatched($userId, $contentType);

        if (!$lastWatched) {
            return $this->notFoundResponse('No watch history found');
        }

        $channel = $lastWatched->channel;
        if (!$channel) {
            $channel = Channel::where('external_id', (string) $lastWatched->channel_id)->first();
        }
        $channelData = $channel ? (new ChannelDetailResource($channel))->resolve() : null;

        return $this->successResponse([
            'id' => $lastWatched->channel_id,
            'type' => $lastWatched->content_type,
            'watched_at' => $lastWatched->watched_at,
            'channel' => $channelData,
            'episode_id' => $lastWatched->episode_id,
            'episode_season' => $lastWatched->episode_season,
            'episode_number' => $lastWatched->episode_number,
            'episode_title' => $lastWatched->episode_title,
            'episode_description' => $lastWatched->episode_description,
            'episode_thumbnail' => $lastWatched->episode_thumbnail,
            'episode_stream_url' => $lastWatched->episode_stream_url,
        ]);
    }

    public function lastMe(?string $contentType = null): JsonResponse
    {
        $userId = (string) auth('api')->id();
        return $this->last($userId, $contentType);
    }

    public function index(string $userId, ?string $contentType = null): JsonResponse
    {
        $history = $this->service->getHistory($userId, $contentType);

        return $this->paginatedResponse(
            $history,
            'History retrieved successfully'
        );
    }

    public function indexMe(?string $contentType = null): JsonResponse
    {
        $userId = (string) auth('api')->id();
        return $this->index($userId, $contentType);
    }

    public function store(string $userId): JsonResponse
    {
        $validated = request()->validate([
            'channel_id' => 'required|integer',
            'content_type' => 'required|in:channel,movie,series',
            'episode_id' => 'nullable|integer',
            'episode_season' => 'nullable|integer',
            'episode_number' => 'nullable|integer',
            'episode_title' => 'nullable|string',
            'episode_description' => 'nullable|string',
            'episode_thumbnail' => 'nullable|string',
            'episode_stream_url' => 'nullable|string',
        ]);

        $this->service->addToHistory(
            $userId,
            $validated['channel_id'],
            $validated['content_type'],
            [
                'episode_id' => $validated['episode_id'] ?? null,
                'episode_season' => $validated['episode_season'] ?? null,
                'episode_number' => $validated['episode_number'] ?? null,
                'episode_title' => $validated['episode_title'] ?? null,
                'episode_description' => $validated['episode_description'] ?? null,
                'episode_thumbnail' => $validated['episode_thumbnail'] ?? null,
                'episode_stream_url' => $validated['episode_stream_url'] ?? null,
            ]
        );

        return $this->successResponse(null, 'Added to history');
    }

    public function storeMe(): JsonResponse
    {
        $userId = (string) auth('api')->id();
        return $this->store($userId);
    }

    public function clear(string $userId): JsonResponse
    {
        $this->service->clearHistory($userId);

        return $this->successResponse(null, 'History cleared successfully');
    }

    public function clearMe(): JsonResponse
    {
        $userId = (string) auth('api')->id();
        return $this->clear($userId);
    }

    public function sync(): JsonResponse
    {
        // This endpoint is called by PWA Service Worker for background sync
        // Currently a no-op since history is synced via regular API calls
        return $this->successResponse(null, 'History sync completed');
    }
}
