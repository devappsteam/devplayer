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

    /**
     * Sync episodes from series data (bulk insert/update)
     */
    public function syncEpisodesFromSeriesData(int $channelId, array $seasonsData): int
    {
        $count = 0;

        foreach ($seasonsData as $seasonData) {
            $seasonNumber = $seasonData['season'] ?? 1;
            $episodes = $seasonData['episodes'] ?? [];

            foreach ($episodes as $episodeData) {
                $episodeNumber = $episodeData['episode'] ?? 1;

                // Parse duration to minutes
                $duration = $this->parseDuration($episodeData);

                // Update or create episode
                Episode::updateOrCreate(
                    [
                        'channel_id' => $channelId,
                        'season' => $seasonNumber,
                        'episode' => $episodeNumber,
                    ],
                    [
                        'name' => $episodeData['title'] ?? $episodeData['name'] ?? 'Episode ' . $episodeNumber,
                        'description' => $episodeData['description'] ?? null,
                        'plot' => $episodeData['plot'] ?? $episodeData['description'] ?? null,
                        'aired_date' => $episodeData['aired_date'] ? \Carbon\Carbon::parse($episodeData['aired_date']) : null,
                        'duration' => $duration,
                        'thumbnail_url' => $episodeData['thumbnail'] ?? $episodeData['cover'] ?? null,
                        'external_id' => $episodeData['id'] ?? $episodeData['episode_id'] ?? null,
                        'metadata' => [
                            'stream_url' => $episodeData['stream_url'] ?? null,
                            'rating' => $episodeData['rating'] ?? null,
                            'raw_data' => $episodeData['raw_data'] ?? null,
                        ],
                        'is_active' => true,
                    ]
                );

                $count++;
            }
        }

        return $count;
    }

    /**
     * Parse duration to minutes from various formats
     */
    protected function parseDuration(array $episodeData): ?int
    {
        // Try duration_secs first (from info.duration_secs)
        if (isset($episodeData['raw_data']['info']['duration_secs'])) {
            return (int) ceil($episodeData['raw_data']['info']['duration_secs'] / 60);
        }

        // Try duration in HH:MM:SS format
        $duration = $episodeData['duration'] ?? null;
        if ($duration && is_string($duration)) {
            // Match HH:MM:SS format
            if (preg_match('/^(\d+):(\d+):(\d+)$/', $duration, $matches)) {
                $hours = (int) $matches[1];
                $minutes = (int) $matches[2];
                $seconds = (int) $matches[3];
                return ($hours * 60) + $minutes + (int) ceil($seconds / 60);
            }
        }

        // Try numeric duration (already in minutes)
        if (is_numeric($duration)) {
            return (int) $duration;
        }

        return null;
    }

    /**
     * Get episodes count for channel
     */
    public function countByChannelId(int $channelId): int
    {
        return Episode::where('channel_id', $channelId)->count();
    }

    /**
     * Clear all episodes for channel
     */
    public function clearByChannelId(int $channelId): int
    {
        return Episode::where('channel_id', $channelId)->delete();
    }
}
