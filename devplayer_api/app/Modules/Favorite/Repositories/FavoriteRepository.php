<?php

namespace App\Modules\Favorite\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Favorite\Repositories\Contracts\FavoriteRepositoryInterface;
use App\Modules\Favorite\Models\Favorite;

class FavoriteRepository extends BaseRepository implements FavoriteRepositoryInterface
{
    public function __construct(Favorite $model)
    {
        parent::__construct($model);
    }

    /**
     * Find favorite by user_id and channel_id
     */
    public function findByUserAndChannel(int $userId, int $channelId): ?Favorite
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('channel_id', $channelId)
            ->first();
    }

    /**
     * Get user's favorites with channel details, ordered
     */
    public function getUserFavoritesWithChannel(int $userId)
    {
        return $this->query()
            ->where('user_id', $userId)
            ->with('channel')
            ->ordered()
            ->get();
    }
}
