<?php

namespace App\Modules\IPTV\Helpers;

use Illuminate\Support\Facades\Cache;

class M3UStreamUrlManager
{
    /**
     * Store stream URLs in cache for later retrieval
     */
    public static function store(int $iptvId, array $streamUrls): void
    {
        $cacheKey = "m3u_stream_urls_{$iptvId}";
        Cache::put($cacheKey, $streamUrls, now()->addDays(7));
    }

    /**
     * Get stored stream URL by channel name
     */
    public static function get(int $iptvId, string $channelName): ?string
    {
        $cacheKey = "m3u_stream_urls_{$iptvId}";
        $urls = Cache::get($cacheKey, []);

        return $urls[$channelName] ?? null;
    }

    /**
     * Clear stored stream URLs
     */
    public static function clear(int $iptvId): void
    {
        $cacheKey = "m3u_stream_urls_{$iptvId}";
        Cache::forget($cacheKey);
    }
}
