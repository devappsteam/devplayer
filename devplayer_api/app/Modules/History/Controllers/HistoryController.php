<?php

namespace App\Modules\History\Controllers;

use App\Http\Controllers\Controller;
use App\Core\Traits\ApiResponse;
use App\Modules\History\Services\HistoryService;
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

        return $this->successResponse([
            'id' => $lastWatched->channel_id,
            'type' => $lastWatched->content_type,
            'watched_at' => $lastWatched->watched_at,
        ]);
    }

    public function index(string $userId, ?string $contentType = null): JsonResponse
    {
        $history = $this->service->getHistory($userId, $contentType);

        return $this->paginatedResponse(
            $history,
            'History retrieved successfully'
        );
    }

    public function store(string $userId): JsonResponse
    {
        $validated = request()->validate([
            'channel_id' => 'required|integer',
            'content_type' => 'required|in:channel,movie,series',
        ]);

        $this->service->addToHistory(
            $userId,
            $validated['channel_id'],
            $validated['content_type']
        );

        return $this->successResponse(null, 'Added to history');
    }

    public function clear(string $userId): JsonResponse
    {
        $this->service->clearHistory($userId);

        return $this->successResponse(null, 'History cleared successfully');
    }

    public function sync(): JsonResponse
    {
        // This endpoint is called by PWA Service Worker for background sync
        // Currently a no-op since history is synced via regular API calls
        return $this->successResponse(null, 'History sync completed');
    }
}
