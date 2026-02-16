<?php

namespace App\Modules\Favorite\Controllers;

use App\Http\Controllers\Controller;
use App\Core\Traits\ApiResponse;
use App\Modules\Favorite\Services\FavoriteService;
use App\Modules\Favorite\Requests\StoreFavoriteRequest;
use App\Modules\Favorite\Requests\UpdateFavoriteRequest;
use App\Modules\Favorite\Resources\FavoriteResource;
use App\Modules\Favorite\Models\Favorite;
use Illuminate\Http\JsonResponse;

class FavoriteController extends Controller
{
    use ApiResponse;

    protected FavoriteService $service;

    public function __construct(FavoriteService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        $favorites = $this->service->paginate(
            perPage: request('per_page', 15)
        );

        return $this->paginatedResponse(
            $favorites,
            'Favorites retrieved successfully'
        );
    }

    public function store(StoreFavoriteRequest $request): JsonResponse
    {
        $favorite = $this->service->create($request->validated());

        return $this->createdResponse(
            new FavoriteResource($favorite),
            'Favorite created successfully'
        );
    }

    public function show(string $uuid): JsonResponse
    {
        $favorite = $this->service->findByUuid($uuid);

        if (!$favorite) {
            return $this->notFoundResponse('Favorite not found');
        }

        return $this->successResponse(
            new FavoriteResource($favorite),
            'Favorite retrieved successfully'
        );
    }

    public function update(UpdateFavoriteRequest $request, string $uuid): JsonResponse
    {
        $favorite = $this->service->findByUuid($uuid);

        if (!$favorite) {
            return $this->notFoundResponse('Favorite not found');
        }

        $updated = $this->service->update($favorite, $request->validated());

        if (!$updated) {
            return $this->serverErrorResponse('Failed to update favorite');
        }

        return $this->successResponse(
            new FavoriteResource($favorite->fresh()),
            'Favorite updated successfully'
        );
    }

    public function destroy(string $uuid): JsonResponse
    {
        $favorite = $this->service->findByUuid($uuid);

        if (!$favorite) {
            return $this->notFoundResponse('Favorite not found');
        }

        $deleted = $this->service->delete($favorite);

        if (!$deleted) {
            return $this->serverErrorResponse('Failed to delete favorite');
        }

        return $this->successResponse(
            null,
            'Favorite deleted successfully'
        );
    }

    /**
     * Get user's favorites with channel details
     */
    public function userFavorites(int $userId): JsonResponse
    {
        $streamType = request('type'); // Optional filter: live, vod, series

        if ($streamType) {
            $favorites = $this->service->getUserFavoritesWithChannel($userId)
                ->where('stream_type', $streamType);
        } else {
            $favorites = $this->service->getUserFavoritesWithChannel($userId);
        }

        return $this->successResponse(
            $favorites->map(function ($favorite) {
                // Check if channel exists to prevent null pointer errors
                if (!$favorite->channel) {
                    return [
                        'id' => $favorite->id,
                        'uuid' => $favorite->uuid,
                        'name' => 'Channel not found',
                        'logo_url' => null,
                        'stream_url' => null,
                        'type' => $favorite->stream_type,
                        'stream_type' => $favorite->stream_type,
                    ];
                }

                return [
                    'id' => $favorite->channel->id,
                    'uuid' => $favorite->channel->uuid,  // Usar UUID do channel, não do favorite
                    'name' => $favorite->channel->name,
                    'logo_url' => $favorite->channel->logo_url,
                    'stream_url' => $favorite->channel->stream_url,
                    'type' => $favorite->channel->stream_type,
                    'stream_type' => $favorite->stream_type,
                ];
            })->values(),
            'User favorites retrieved successfully'
        );
    }

    /**
     * Toggle favorite for a channel
     */
    public function toggle(): JsonResponse
    {
        $userId = request('user_id', 1); // Default user_id = 1
        $channelId = request('channel_id');
        $streamType = request('stream_type', 'live'); // Default to 'live'

        if (!$channelId) {
            return $this->validationErrorResponse(['channel_id' => ['Channel ID is required']]);
        }

        // Check if favorite exists (including soft-deleted)
        $existing = Favorite::withTrashed()
            ->where('user_id', $userId)
            ->where('channel_id', $channelId)
            ->where('stream_type', $streamType)
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                // Restore soft-deleted favorite
                $existing->restore();
                $this->service->clearUserCache($userId);

                return $this->successResponse(
                    ['favorited' => true],
                    'Re-added to favorites'
                );
            } else {
                // Remove from favorites
                $this->service->delete($existing);
                $this->service->clearUserCache($userId);

                return $this->successResponse(
                    ['favorited' => false],
                    'Removed from favorites'
                );
            }
        } else {
            // Add to favorites
            $favorite = $this->service->create([
                'user_id' => $userId,
                'channel_id' => $channelId,
                'stream_type' => $streamType,
            ]);

            // Clear cache
            $this->service->clearUserCache($userId);

            return $this->createdResponse(
                ['favorited' => true, 'uuid' => $favorite->uuid],
                'Added to favorites'
            );
        }
    }

    /**
     * Check if channel is favorited by user
     */
    public function check(int $userId, int $channelId): JsonResponse
    {
        $exists = $this->service->findByUserAndChannel($userId, $channelId) !== null;

        return $this->successResponse(
            ['favorited' => $exists],
            'Favorite status checked'
        );
    }

    /**
     * Background sync endpoint for PWA Service Worker
     */
    public function sync(): JsonResponse
    {
        // This endpoint is called by PWA Service Worker for background sync
        // Currently a no-op since favorites are synced via regular API calls
        return $this->successResponse(null, 'Favorites sync completed');
    }

    /**
     * Fix stream_type for existing favorites based on channel type
     */
    public function fixStreamTypes(): JsonResponse
    {
        try {
            $favorites = \App\Modules\Favorite\Models\Favorite::with('channel')->get();
            $fixed = 0;
            $errors = 0;

            foreach ($favorites as $favorite) {
                if (!$favorite->channel) {
                    $errors++;
                    continue;
                }

                $correctType = $favorite->channel->stream_type;

                if ($favorite->stream_type !== $correctType) {
                    $favorite->stream_type = $correctType;
                    $favorite->save();
                    $fixed++;
                }
            }

            // Clear all user caches
            \Illuminate\Support\Facades\Cache::flush();

            return $this->successResponse(
                [
                    'total' => $favorites->count(),
                    'fixed' => $fixed,
                    'errors' => $errors,
                ],
                "Fixed {$fixed} favorites stream types"
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Error fixing stream types: ' . $e->getMessage());
        }
    }
}
