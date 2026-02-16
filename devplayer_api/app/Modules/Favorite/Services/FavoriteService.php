<?php

namespace App\Modules\Favorite\Services;

use App\Core\Services\BaseService;
use App\Modules\Favorite\Repositories\Contracts\FavoriteRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class FavoriteService extends BaseService
{
    public function __construct(FavoriteRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    // Add custom methods specific to Favorite here

    /**
     * Find favorite by user_id and channel_id
     */
    public function findByUserAndChannel(int $userId, int $channelId)
    {
        return $this->repository->findByUserAndChannel($userId, $channelId);
    }

    /**
     * Get user's favorites with channel details (cached)
     */
    public function getUserFavoritesWithChannel(int $userId)
    {
        $cacheKey = "user_{$userId}_favorites";

        return Cache::remember($cacheKey, 3600, function () use ($userId) {
            return $this->repository->getUserFavoritesWithChannel($userId);
        });
    }

    /**
     * Clear user favorites cache
     */
    public function clearUserCache(int $userId): void
    {
        Cache::forget("user_{$userId}_favorites");
    }
}
