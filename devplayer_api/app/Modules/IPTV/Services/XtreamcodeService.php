<?php

namespace App\Modules\IPTV\Services;

use App\Modules\IPTV\Models\IPTV;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Exception;

class XtreamcodeService
{
    protected IPTV $iptv;
    protected string $baseUrl;
    protected string $username;
    protected string $password;

    public function __construct(IPTV $iptv)
    {
        $this->iptv = $iptv;
        $this->baseUrl = rtrim($iptv->url, '/');
        $this->username = $iptv->username;
        $this->password = $iptv->password;
    }

    /**
     * Get VOD (Movie) information from Xtreamcode
     */
    public function getVodInfo(int|string $vodId): ?array
    {
        try {
            $cacheKey = "xtreamcode_vod_{$this->iptv->id}_{$vodId}";

            // Cache for 24 hours
            return Cache::remember($cacheKey, 86400, function () use ($vodId) {
                $url = "{$this->baseUrl}/player_api.php";

                $response = Http::timeout(10)->get($url, [
                    'username' => $this->username,
                    'password' => $this->password,
                    'action' => 'get_vod_info',
                    'vod_id' => (int) $vodId,
                ]);

                if ($response->failed()) {
                    return null;
                }

                $data = $response->json();

                // Validate response structure
                if (!is_array($data) || empty($data)) {
                    return null;
                }

                // Normalize episode data for series-like VOD content
                return $this->normalizeVodData($data, $vodId);
            });
        } catch (Exception $e) {
            \Log::error("Error fetching VOD info from Xtreamcode: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Get Series information from Xtreamcode with episodes
     */
    public function getSeriesInfo(int|string $seriesId): ?array
    {
        try {
            $cacheKey = "xtreamcode_series_{$this->iptv->id}_{$seriesId}";

            // Cache for 24 hours
            return Cache::remember($cacheKey, 86400, function () use ($seriesId) {
                $url = "{$this->baseUrl}/player_api.php";

                $response = Http::timeout(10)->get($url, [
                    'username' => $this->username,
                    'password' => $this->password,
                    'action' => 'get_series_info',
                    'series_id' => (int) $seriesId,
                ]);

                if ($response->failed()) {
                    return null;
                }

                $data = $response->json();

                // Validate response structure
                if (!is_array($data) || empty($data)) {
                    return null;
                }

                return $this->normalizeSeriesData($data, $seriesId);
            });
        } catch (Exception $e) {
            \Log::error("Error fetching Series info from Xtreamcode: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Build stream URL for VOD
     */
    public function buildVodStreamUrl(int|string|null $vodId, ?string $containerExtension = null): ?string
    {
        if (is_null($vodId)) {
            return null;
        }
        $extension = $containerExtension ?? 'mp4';
        return "{$this->baseUrl}/movie/{$this->username}/{$this->password}/{$vodId}.{$extension}";
    }

    /**
     * Build stream URL for episode
     */
    public function buildEpisodeStreamUrl(int|string|null $episodeId): ?string
    {
        if (is_null($episodeId)) {
            return null;
        }

        return "{$this->baseUrl}/series/{$this->username}/{$this->password}/{$episodeId}.mp4";
    }

    /**
     * Normalize VOD data from Xtreamcode response
     */
    protected function normalizeVodData(array $data, int|string|null $fallbackVodId = null): array
    {
        $vod = $data['info'] ?? $data;
        $vodId = $vod['id'] ?? $vod['vod_id'] ?? $fallbackVodId;
        $containerExtension = $vod['container_extension'] ?? null;

        return [
            'id' => $vodId,
            'name' => $vod['name'] ?? $vod['title'] ?? null,
            'description' => $vod['description'] ?? null,
            'plot' => $vod['plot'] ?? null,
            'duration' => $vod['duration'] ?? null,
            'rating' => $vod['rating'] ?? null,
            'release_date' => $vod['releasedate'] ?? $vod['release_date'] ?? null,
            'release_year' => $vod['year'] ?? null,
            'genre' => $vod['genre'] ?? null,
            'director' => $vod['director'] ?? null,
            'cast' => $vod['cast'] ?? $vod['actors'] ?? null,
            'cover' => $vod['cover'] ?? null,
            'poster' => $vod['poster'] ?? null,
            'backdrop' => $vod['backdrop'] ?? null,
            'tmdb_id' => $vod['tmdb_id'] ?? null,
            'imdb_id' => $vod['imdb_id'] ?? null,
            'rating_count' => $vod['rating_count'] ?? null,
            'stream_url' => $this->buildVodStreamUrl($vodId, $containerExtension),
            'raw_data' => $data, // Keep raw data for debugging
        ];
    }

    /**
     * Normalize Series data from Xtreamcode response
     */
    protected function normalizeSeriesData(array $data, int|string|null $fallbackSeriesId = null): array
    {
        $series = $data['info'] ?? $data;
        $episodes = $data['episodes'] ?? [];
        $seasonsMeta = $data['seasons'] ?? []; // Season metadata from Xtreamcode
        $seriesId = $series['id'] ?? $series['series_id'] ?? $fallbackSeriesId;

        // Group episodes by season
        $groupedEpisodes = $this->groupEpisodesBySeason($episodes, $seasonsMeta);

        return [
            'id' => $seriesId,
            'name' => $series['name'] ?? $series['title'] ?? null,
            'description' => $series['description'] ?? null,
            'plot' => $series['plot'] ?? null,
            'rating' => $series['rating'] ?? null,
            'release_date' => $series['releasedate'] ?? $series['release_date'] ?? null,
            'release_year' => $series['year'] ?? null,
            'genre' => $series['genre'] ?? null,
            'director' => $series['director'] ?? null,
            'cast' => $series['cast'] ?? $series['actors'] ?? null,
            'cover' => $this->extractImageUrl($series['cover'] ?? null),
            'poster' => $this->extractImageUrl($series['poster'] ?? null),
            'backdrop' => $this->extractImageUrl($series['backdrop'] ?? null),
            'backdrop_path' => $this->extractImageUrl(isset($series['backdrop_path'][0]) ? $series['backdrop_path'][0] : null),
            'tmdb_id' => $series['tmdb_id'] ?? null,
            'imdb_id' => $series['imdb_id'] ?? null,
            'rating_count' => $series['rating_count'] ?? null,
            'seasons' => $groupedEpisodes,
            'episodes_count' => count(array_reduce($episodes, function($carry, $item) { return array_merge($carry, (array) $item); }, [])),
            'raw_data' => $data, // Keep raw data for debugging
        ];
    }

    /**
     * Group episodes by season
     * Xtreamcode returns episodes as: { "1": [...episodes], "2": [...episodes] }
     * or as flat array of episodes if not structured yet
     */
    protected function groupEpisodesBySeason(array $episodes, array $seasonsMeta = []): array
    {
        $grouped = [];

        // First, create all seasons from seasonsMeta
        foreach ($seasonsMeta as $seasonInfo) {
            $seasonNum = (int) ($seasonInfo['season_number'] ?? $seasonInfo['season'] ?? 1);
            if (!isset($grouped[$seasonNum])) {
                $grouped[$seasonNum] = [
                    'season' => $seasonNum,
                    'episodes' => [],
                ];
            }
        }

        // Check if episodes is structured by season (Xtreamcode format)
        // or if it's just a flat list of episodes
        if (!empty($episodes) && is_array(reset($episodes)) && isset(reset($episodes)['season'])) {
            // Already has season info in each episode - structure is flat
            foreach ($episodes as $episodeId => $episodeData) {
                $season = (int) ($episodeData['season'] ?? 1);
                $episode = (int) ($episodeData['episode_num'] ?? 1);

                if (!isset($grouped[$season])) {
                    $grouped[$season] = [
                        'season' => $season,
                        'episodes' => [],
                    ];
                }

                $grouped[$season]['episodes'][] = [
                    'id' => $episodeData['id'] ?? $episodeId,
                    'episode_id' => $episodeData['id'] ?? $episodeId,
                    'season' => $season,
                    'episode' => $episode,
                    'title' => $episodeData['title'] ?? null,
                    'name' => $episodeData['title'] ?? null,
                    'description' => $episodeData['info']['plot'] ?? $episodeData['description'] ?? null,
                    'plot' => $episodeData['info']['plot'] ?? $episodeData['plot'] ?? null,
                    'duration' => $episodeData['info']['duration'] ?? $episodeData['duration'] ?? null,
                    'rating' => $episodeData['info']['rating'] ?? $episodeData['rating'] ?? null,
                    'aired_date' => $episodeData['info']['release_date'] ?? $episodeData['aired'] ?? $episodeData['aired_date'] ?? null,
                    'thumbnail' => $episodeData['info']['movie_image'] ?? $episodeData['thumbnail'] ?? null,
                    'cover' => $episodeData['info']['cover_big'] ?? $episodeData['cover'] ?? null,
                    'stream_url' => $this->buildEpisodeStreamUrl($episodeData['id'] ?? $episodeId),
                    'raw_data' => $episodeData,
                ];
            }
        } else if (!empty($episodes) && is_array(reset($episodes))) {
            // Episodes structured as { "1": [...], "2": [...] }
            // This is the Xtreamcode native format
            foreach ($episodes as $seasonNum => $seasonEpisodes) {
                if (!is_array($seasonEpisodes)) {
                    continue;
                }

                $season = (int) $seasonNum;

                if (!isset($grouped[$season])) {
                    $grouped[$season] = [
                        'season' => $season,
                        'episodes' => [],
                    ];
                }

                foreach ($seasonEpisodes as $episodeData) {
                    $episodeNum = (int) ($episodeData['episode_num'] ?? 1);

                    $grouped[$season]['episodes'][] = [
                        'id' => $episodeData['id'] ?? null,
                        'episode_id' => $episodeData['id'] ?? null,
                        'season' => $season,
                        'episode' => $episodeNum,
                        'title' => $episodeData['title'] ?? null,
                        'name' => $episodeData['title'] ?? null,
                        'description' => $episodeData['info']['plot'] ?? $episodeData['description'] ?? null,
                        'plot' => $episodeData['info']['plot'] ?? $episodeData['plot'] ?? null,
                        'duration' => $episodeData['info']['duration'] ?? $episodeData['duration'] ?? null,
                        'rating' => $episodeData['info']['rating'] ?? $episodeData['rating'] ?? null,
                        'aired_date' => $episodeData['info']['release_date'] ?? $episodeData['aired'] ?? $episodeData['aired_date'] ?? null,
                        'thumbnail' => $episodeData['info']['movie_image'] ?? $episodeData['thumbnail'] ?? null,
                        'cover' => $episodeData['info']['cover_big'] ?? $episodeData['cover'] ?? null,
                        'stream_url' => $this->buildEpisodeStreamUrl($episodeData['id'] ?? null),
                        'raw_data' => $episodeData,
                    ];
                }
            }
        }

        // Sort by season number
        ksort($grouped);

        // Sort episodes within each season
        foreach ($grouped as &$season) {
            usort($season['episodes'], function ($a, $b) {
                return $a['episode'] <=> $b['episode'];
            });
        }

        return array_values($grouped);
    }

    /**
     * Clear cache for a specific VOD
     */
    public function clearVodCache(int|string $vodId): void
    {
        $cacheKey = "xtreamcode_vod_{$this->iptv->id}_{$vodId}";
        Cache::forget($cacheKey);
    }

    /**
     * Clear cache for a specific Series
     */
    public function clearSeriesCache(int|string $seriesId): void
    {
        $cacheKey = "xtreamcode_series_{$this->iptv->id}_{$seriesId}";
        Cache::forget($cacheKey);
    }

    /**
     * Extract image URL from field that can be array or string
     * Xtreamcode can return image fields as array with URLs or as string
     */
    protected function extractImageUrl($imageData): ?string
    {
        if (is_null($imageData)) {
            return null;
        }

        // If it's an array, get the first URL
        if (is_array($imageData) && !empty($imageData)) {
            return $imageData[0];
        }

        // If it's already a string, return it
        if (is_string($imageData) && !empty($imageData)) {
            return $imageData;
        }

        return null;
    }
}
