<?php

namespace App\Modules\Channel\Services;

use App\Modules\Channel\Models\Episode;
use Illuminate\Pagination\Paginator;

class EpisodeService
{
    /**
     * Get paginated episodes
     */
    public function paginate($perPage = 15): Paginator
    {
        return Episode::query()
            ->paginate($perPage);
    }

    /**
     * Find episode by UUID
     */
    public function findByUuid(string $uuid): ?Episode
    {
        return Episode::query()
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * Get episodes by channel UUID
     */
    public function getByChannelUuid(string $channelUuid, $perPage = 50): Paginator
    {
        return Episode::query()
            ->whereHas('channel', function ($query) use ($channelUuid) {
                $query->where('uuid', $channelUuid);
            })
            ->orderBy('season')
            ->orderBy('episode')
            ->paginate($perPage);
    }

    /**
     * Get episodes by channel ID
     */
    public function getByChannelId(int $channelId, $perPage = 50): Paginator
    {
        return Episode::query()
            ->where('channel_id', $channelId)
            ->orderBy('season')
            ->orderBy('episode')
            ->paginate($perPage);
    }

    /**
     * Get episodes grouped by season
     */
    public function getGroupedByChannelId(int $channelId): array
    {
        return Episode::query()
            ->where('channel_id', $channelId)
            ->orderBy('season')
            ->orderBy('episode')
            ->get()
            ->groupBy('season')
            ->map(function ($episodes) {
                return $episodes->values();
            })
            ->toArray();
    }

    /**
     * Create new episode
     */
    public function create(array $data): Episode
    {
        return Episode::create($data);
    }

    /**
     * Update episode
     */
    public function update(Episode $episode, array $data): bool
    {
        return $episode->update($data);
    }

    /**
     * Delete episode
     */
    public function delete(Episode $episode): bool
    {
        return $episode->delete();
    }
}
