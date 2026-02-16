<?php

namespace App\Modules\IPTV\Helpers;

use App\Modules\Channel\Enums\StreamType;
use Illuminate\Support\Facades\Http;

class M3UParser
{
    /**
     * Parse M3U from URL
     */
    public static function parseFromUrl(string $url): array
    {
        try {
            $response = Http::timeout(60)->get($url);

            if (!$response->successful()) {
                throw new \Exception("Failed to fetch M3U from URL: HTTP {$response->status()}");
            }

            return self::parseM3UContent($response->body());
        } catch (\Exception $e) {
            throw new \Exception("M3U parsing error: {$e->getMessage()}");
        }
    }

    /**
     * Parse M3U from file
     */
    public static function parseFromFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \Exception("M3U file not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        return self::parseM3UContent($content);
    }

    /**
     * Parse M3U content
     */
    public static function parseM3UContent(string $content): array
    {
        $lines = explode("\n", $content);
        $channels = [];
        $currentEntry = null;

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines and header
            if (empty($line) || $line === '#EXTM3U') {
                continue;
            }

            // Parse EXTINF line
            if (strpos($line, '#EXTINF:') === 0) {
                $currentEntry = self::parseExtInfo($line);
            } elseif ($currentEntry !== null && !empty($line) && strpos($line, '#') !== 0) {
                // This is the stream URL
                $currentEntry['url'] = trim($line);
                $channels[] = $currentEntry;
                $currentEntry = null;
            }
        }

        return self::categorizeChannels($channels);
    }

    /**
     * Parse EXTINF line to extract channel info
     * Format: #EXTINF:-1 tvg-name="..." tvg-logo="..." group-title="...",Channel Name
     */
    protected static function parseExtInfo(string $line): array
    {
        $entry = [
            'name' => '',
            'logo_url' => '',
            'group_title' => '',
            'tvg_name' => '',
            'url' => '',
        ];

        // Extract channel name (after last comma)
        if (preg_match('/,(.+?)$/', $line, $matches)) {
            $entry['name'] = trim($matches[1]);
        }

        // Extract tvg-name
        if (preg_match('/tvg-name="([^"]*)"/', $line, $matches)) {
            $entry['tvg_name'] = $matches[1];
        }

        // Extract tvg-logo
        if (preg_match('/tvg-logo="([^"]*)"/', $line, $matches)) {
            $entry['logo_url'] = $matches[1];
        }

        // Extract group-title
        if (preg_match('/group-title="([^"]*)"/', $line, $matches)) {
            $entry['group_title'] = $matches[1];
        }

        return $entry;
    }

    /**
     * Categorize channels by group-title
     * Returns array grouped by stream type
     */
    protected static function categorizeChannels(array $channels): array
    {
        $categorized = [
            'live' => [],
            'vod' => [],
            'series' => [],
        ];

        foreach ($channels as $channel) {
            $groupTitle = strtolower(trim($channel['group_title']));

            // Determine stream type based on group-title
            $streamType = self::determineStreamType($groupTitle);

            // Create category name from group-title
            $categoryName = self::extractCategoryName($groupTitle);

            $channelData = [
                'name' => $channel['name'],
                'logo_url' => $channel['logo_url'],
                'tvg_name' => $channel['tvg_name'],
                'url' => $channel['url'],
                'group_title' => $channel['group_title'],
                'category_name' => $categoryName,
            ];

            $categorized[$streamType][] = $channelData;
        }

        // Group channels by category within each type
        return self::groupByCategory($categorized);
    }

    /**
     * Determine stream type from group-title
     */
    protected static function determineStreamType(string $groupTitle): string
    {
        // Check for Series indicators
        if (preg_match('/(séries|series|serie)/i', $groupTitle)) {
            return 'series';
        }

        // Check for VOD/Movie indicators
        if (preg_match('/(filmes|films|movie|vod|cinema)/i', $groupTitle)) {
            return 'vod';
        }

        // Check for Live TV indicators (default)
        if (preg_match('/(canais|channels|tv|live|ao vivo)/i', $groupTitle)) {
            return 'live';
        }

        // Default to live if no specific indicator found
        return 'live';
    }

    /**
     * Extract category name from group-title
     * Examples:
     *   "Series | Globoplay" → "Globoplay"
     *   "Canais | News" → "News"
     *   "Filmes | Ação" → "Ação"
     */
    protected static function extractCategoryName(string $groupTitle): string
    {
        // If contains pipe, take the part after it
        if (strpos($groupTitle, '|') !== false) {
            $parts = explode('|', $groupTitle);
            return trim(array_pop($parts));
        }

        // Otherwise use the entire group-title as category
        return $groupTitle;
    }

    /**
     * Group channels by category within each stream type
     */
    protected static function groupByCategory(array $categorized): array
    {
        $result = [];

        foreach ($categorized as $type => $channels) {
            $groups = [];

            foreach ($channels as $channel) {
                $categoryName = $channel['category_name'];
                if (!isset($groups[$categoryName])) {
                    $groups[$categoryName] = [];
                }
                $groups[$categoryName][] = $channel;
            }

            $result[$type] = $groups;
        }

        return $result;
    }

    /**
     * Convert parsed M3U to Xtream-like format for compatibility
     */
    public static function toXtreamFormat(array $parsedM3U): array
    {
        $result = [
            'live_categories' => [],
            'live_streams' => [],
            'vod_categories' => [],
            'vod_streams' => [],
            'series_categories' => [],
            'series' => [],
        ];

        $categoryIdMap = [
            'live' => [],
            'vod' => [],
            'series' => [],
        ];

        // Process each stream type
        foreach (['live', 'vod', 'series'] as $type) {
            if (!isset($parsedM3U[$type])) {
                continue;
            }

            $categoryId = 1;

            foreach ($parsedM3U[$type] as $categoryName => $channels) {
                // Add category
                $categoriesKey = "{$type}_categories";
                $result[$categoriesKey][] = [
                    'category_id' => $categoryId,
                    'category_name' => $categoryName,
                ];

                $categoryIdMap[$type][$categoryName] = $categoryId;

                // Add channels
                $streamsKey = "{$type}_streams";
                $streamId = 1;

                foreach ($channels as $channel) {
                    $streamData = [
                        'stream_id' => $categoryId * 1000 + $streamId,
                        'num' => $streamId,
                        'name' => $channel['name'],
                        'category_id' => $categoryId,
                        'stream_icon' => $channel['logo_url'],
                        'epg_channel_id' => $channel['tvg_name'] ?? '',
                    ];

                    // Add type-specific data
                    if ($type === 'live') {
                        $streamData['added'] = time();
                        $streamData['rating'] = '0';
                        $streamData['rating_5based'] = '0';
                        $result['live_streams'][] = $streamData;
                    } elseif ($type === 'vod') {
                        $streamData['container_extension'] = 'mkv';
                        $streamData['added'] = time();
                        $streamData['rating'] = '0';
                        $streamData['rating_5based'] = '0';
                        $streamData['plot'] = '';
                        $streamData['cast'] = '';
                        $streamData['director'] = '';
                        $streamData['genre'] = '';
                        $streamData['releasedate'] = '';
                        $streamData['duration'] = '';
                        $streamData['youtube_trailer'] = '';
                        $streamData['cover_big'] = $channel['logo_url'];
                        $result['vod_streams'][] = $streamData;
                    } elseif ($type === 'series') {
                        $streamData['last_modified'] = time();
                        $streamData['rating'] = '0';
                        $streamData['rating_5based'] = '0';
                        $streamData['plot'] = '';
                        $streamData['cast'] = '';
                        $streamData['director'] = '';
                        $streamData['genre'] = '';
                        $streamData['releaseDate'] = '';
                        $streamData['youtube_trailer'] = '';
                        $streamData['episode_run_time'] = '0';
                        $streamData['backdrop_path'] = [];
                        $streamData['stream_icon'] = $channel['logo_url'];
                        $streamData['cover_big'] = $channel['logo_url'];
                        $streamData['cover'] = $channel['logo_url'];
                        $result['series'][] = $streamData;
                    }

                    $streamId++;
                }

                $categoryId++;
            }
        }

        return $result;
    }

    /**
     * Store stream URLs for later retrieval
     */
    public static function storeStreamUrls(array $parsedM3U): array
    {
        $urls = [];

        foreach ($parsedM3U as $type => $categories) {
            foreach ($categories as $categoryName => $channels) {
                foreach ($channels as $channel) {
                    $urls[$channel['name']] = $channel['url'];
                }
            }
        }

        return $urls;
    }
}
