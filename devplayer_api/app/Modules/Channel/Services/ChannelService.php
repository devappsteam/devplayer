<?php

namespace App\Modules\Channel\Services;

use App\Core\Services\BaseService;
use App\Modules\Channel\Repositories\Contracts\ChannelRepositoryInterface;
use App\Modules\Channel\Models\Channel;
use App\Modules\Channel\Enums\StreamType;
use App\Modules\Favorite\Models\Favorite;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ChannelService extends BaseService
{
    public function __construct(ChannelRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Search channels by name or metadata
     */
    public function search(string $query, array $filters = []): LengthAwarePaginator
    {
        $channelsQuery = Channel::query()
            ->with(['category', 'iptv'])
            ->where('is_active', true);

        // Search in name and metadata
        $channelsQuery->where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
              ->orWhere('metadata->description', 'like', "%{$query}%")
              ->orWhere('metadata->tags', 'like', "%{$query}%");
        });

        // Apply filters
        if (isset($filters['category_id'])) {
            $channelsQuery->where('category_id', $filters['category_id']);
        }

        if (isset($filters['iptv_id'])) {
            $channelsQuery->where('iptv_id', $filters['iptv_id']);
        }

        if (isset($filters['stream_type'])) {
            $channelsQuery->where('stream_type', $filters['stream_type']);
        }

        if (isset($filters['has_epg'])) {
            if ($filters['has_epg']) {
                $channelsQuery->whereNotNull('epg_channel_id');
            } else {
                $channelsQuery->whereNull('epg_channel_id');
            }
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $channelsQuery->orderBy($sortBy, $sortOrder);

        return $channelsQuery->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get channels by category with caching
     */
    public function getByCategory(int $categoryId, int $perPage = 20): LengthAwarePaginator
    {
        return Channel::where('category_id', $categoryId)
            ->where('is_active', true)
            ->with('streams')
            ->orderBy('number')
            ->paginate($perPage);
    }

    /**
     * Get channels by category UUID
     */
    public function getByCategoryUuid(string $categoryUuid, int $perPage = 20): LengthAwarePaginator
    {
        // Find the category by UUID
        $category = \App\Modules\Category\Models\Category::where('uuid', $categoryUuid)->first();

        if (!$category) {
            return Channel::where('is_active', false)->paginate($perPage);
        }

        // Get channels for this category
        return Channel::where('category_id', $category->id)
            ->where('is_active', true)
            ->with('streams')
            ->orderBy('number')
            ->paginate($perPage);
    }

    /**
     * Get channels by IPTV provider
     */
    public function getByIptv(int $iptvId, array $filters = []): LengthAwarePaginator
    {
        $query = Channel::where('iptv_id', $iptvId)
            ->where('is_active', true)
            ->with(['category', 'streams']);

        if (isset($filters['stream_type'])) {
            $query->where('stream_type', $filters['stream_type']);
        }

        return $query->orderBy('name')->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Get channels by stream type
     */
    public function getByType(StreamType $type, int $perPage = 20): LengthAwarePaginator
    {
        return Channel::ofType($type)
            ->active()
            ->with(['category', 'iptv'])
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Get live channels only
     */
    public function getLiveChannels(int $perPage = 20): LengthAwarePaginator
    {
        return Channel::live()
            ->active()
            ->with(['category', 'streams'])
            ->orderBy('number')
            ->paginate($perPage);
    }

    /**
     * Toggle favorite for a user
     */
    public function toggleFavorite(int $userId, string $channelUuid): bool
    {
        $channel = Channel::where('uuid', $channelUuid)->firstOrFail();

        $favorite = Favorite::where('user_id', $userId)
            ->where('channel_id', $channel->id)
            ->first();

        if ($favorite) {
            $favorite->delete();
            Cache::forget("user_{$userId}_favorites");
            return false; // Unfavorited
        }

        // Get next order position
        $maxOrder = Favorite::where('user_id', $userId)->max('order') ?? 0;

        Favorite::create([
            'user_id' => $userId,
            'channel_id' => $channel->id,
            'order' => $maxOrder + 1,
        ]);

        Cache::forget("user_{$userId}_favorites");
        return true; // Favorited
    }

    /**
     * Get user's favorite channels
     */
    public function getFavorites(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return Channel::whereHas('favorites', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->with(['category', 'streams', 'favorites' => function ($query) use ($userId) {
            $query->where('user_id', $userId);
        }])
        ->join('favorites', 'channels.id', '=', 'favorites.channel_id')
        ->where('favorites.user_id', $userId)
        ->orderBy('favorites.order')
        ->select('channels.*')
        ->paginate($perPage);
    }

    /**
     * Reorder user favorites
     */
    public function reorderFavorites(int $userId, array $channelUuids): void
    {
        foreach ($channelUuids as $order => $uuid) {
            $channel = Channel::where('uuid', $uuid)->first();

            if ($channel) {
                Favorite::where('user_id', $userId)
                    ->where('channel_id', $channel->id)
                    ->update(['order' => $order + 1]);
            }
        }

        Cache::forget("user_{$userId}_favorites");
    }

    /**
     * Get EPG data for a channel (placeholder for EPG integration)
     */
    public function getEpg(string $channelUuid, ?string $date = null): array
    {
        $channel = Channel::where('uuid', $channelUuid)->firstOrFail();

        if (!$channel->epg_channel_id) {
            return [];
        }

        $date = $date ?? now()->format('Y-m-d');
        $cacheKey = "epg_{$channel->epg_channel_id}_{$date}";

        return Cache::remember($cacheKey, 3600, function () use ($channel, $date) {
            // TODO: Integrate with EPG provider API
            // For now, return mock data
            return [
                'channel_id' => $channel->epg_channel_id,
                'date' => $date,
                'programs' => [],
            ];
        });
    }

    /**
     * Get recommended channels based on user activity
     */
    public function getRecommended(int $userId, int $limit = 10): Collection
    {
        // Get user's favorite categories
        $favoriteCategories = Favorite::where('user_id', $userId)
            ->join('channels', 'favorites.channel_id', '=', 'channels.id')
            ->pluck('channels.category_id')
            ->unique();

        if ($favoriteCategories->isEmpty()) {
            // Return popular channels if no favorites
            return Channel::active()
                ->withCount('favorites')
                ->orderBy('favorites_count', 'desc')
                ->limit($limit)
                ->get();
        }

        // Get channels from favorite categories
        return Channel::active()
            ->whereIn('category_id', $favoriteCategories)
            ->whereDoesntHave('favorites', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    /**
     * Get channel statistics
     */
    public function getStatistics(string $channelUuid): array
    {
        $channel = Channel::where('uuid', $channelUuid)
            ->withCount('favorites')
            ->firstOrFail();

        return [
            'total_favorites' => $channel->favorites_count,
            'total_streams' => $channel->streams()->count(),
            'healthy_streams' => $channel->streams()->healthy()->count(),
            'is_active' => $channel->is_active,
            'stream_type' => $channel->stream_type->value,
            'has_epg' => !is_null($channel->epg_channel_id),
        ];
    }

    /**
     * Bulk update channel status
     */
    public function bulkUpdateStatus(array $channelUuids, bool $isActive): int
    {
        return Channel::whereIn('uuid', $channelUuids)
            ->update(['is_active' => $isActive]);
    }

    /**
     * Get channels with health issues
     */
    public function getChannelsWithHealthIssues(): Collection
    {
        return Channel::whereHas('streams', function ($query) {
            $query->where('is_available', false)
                  ->orWhere('health_status', 'offline');
        })
        ->with(['streams', 'category'])
        ->get();
    }
}
