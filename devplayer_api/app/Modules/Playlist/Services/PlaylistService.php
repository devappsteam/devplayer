<?php

namespace App\Modules\Playlist\Services;

use App\Core\Services\BaseService;
use App\Modules\Playlist\Repositories\Contracts\PlaylistRepositoryInterface;
use App\Modules\Playlist\Models\Playlist;
use App\Modules\IPTV\Models\IPTV;
use App\Modules\Category\Models\Category;
use App\Modules\Channel\Models\Channel;
use App\Modules\Channel\Enums\StreamType;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class PlaylistService extends BaseService
{
    public function __construct(PlaylistRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Load and parse M3U playlist from URL
     */
    public function loadFromUrl(string $url): array
    {
        $response = Http::timeout(30)->get($url);

        if (!$response->successful()) {
            throw new \Exception("Failed to load playlist from URL: {$url}");
        }

        return $this->parseM3U($response->body());
    }

    /**
     * Parse M3U content
     */
    public function parseM3U(string $content): array
    {
        $lines = explode("\n", $content);
        $channels = [];
        $currentChannel = null;

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines and M3U header
            if (empty($line) || $line === '#EXTM3U') {
                continue;
            }

            // Parse EXTINF line (channel metadata)
            if (str_starts_with($line, '#EXTINF:')) {
                $currentChannel = $this->parseExtInf($line);
            }
            // Parse stream URL
            elseif ($currentChannel && !str_starts_with($line, '#')) {
                $currentChannel['url'] = $line;
                $channels[] = $currentChannel;
                $currentChannel = null;
            }
        }

        return $channels;
    }

    /**
     * Parse EXTINF line
     */
    protected function parseExtInf(string $line): array
    {
        // Remove #EXTINF: prefix
        $line = substr($line, 8);

        // Extract tvg attributes
        $channel = [
            'tvg_id' => $this->extractAttribute($line, 'tvg-id'),
            'tvg_name' => $this->extractAttribute($line, 'tvg-name'),
            'tvg_logo' => $this->extractAttribute($line, 'tvg-logo'),
            'group_title' => $this->extractAttribute($line, 'group-title'),
            'name' => '',
        ];

        // Extract channel name (after comma)
        if (preg_match('/,(.+)$/', $line, $matches)) {
            $channel['name'] = trim($matches[1]);
        }

        // If name is empty, use tvg-name as fallback
        if (empty($channel['name'])) {
            $channel['name'] = $channel['tvg_name'] ?? 'Unknown Channel';
        }

        return $channel;
    }

    /**
     * Extract attribute from EXTINF line
     */
    protected function extractAttribute(string $line, string $attribute): ?string
    {
        $pattern = "/{$attribute}=\"([^\"]*)\"/";
        if (preg_match($pattern, $line, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Normalize and save playlist to database
     */
    public function normalizeAndSave(IPTV $iptv, array $channels): Playlist
    {
        // Create categories from group-title
        $categories = [];
        $normalizedChannels = [];

        foreach ($channels as $channelData) {
            $groupTitle = $channelData['group_title'] ?? 'Uncategorized';

            // Create or get category
            if (!isset($categories[$groupTitle])) {
                $category = Category::firstOrCreate(
                    [
                        'iptv_id' => $iptv->id,
                        'name' => $groupTitle,
                    ],
                    [
                        'order' => count($categories) + 1,
                    ]
                );
                $categories[$groupTitle] = $category;
            }

            // Normalize channel data
            $normalizedChannels[] = [
                'category_id' => $categories[$groupTitle]->id,
                'name' => $channelData['name'],
                'tvg_id' => $channelData['tvg_id'],
                'tvg_name' => $channelData['tvg_name'],
                'logo_url' => $channelData['tvg_logo'],
                'stream_url' => $channelData['url'],
            ];
        }

        // Create playlist record
        $checksum = md5(json_encode($channels));

        $playlist = Playlist::updateOrCreate(
            [
                'iptv_id' => $iptv->id,
            ],
            [
                'raw_data' => $channels,
                'normalized_data' => $normalizedChannels,
                'checksum' => $checksum,
                'expires_at' => now()->addHours(24),
            ]
        );

        return $playlist;
    }

    /**
     * Sync playlist channels to database
     */
    public function syncChannels(Playlist $playlist): int
    {
        $syncedCount = 0;
        $normalizedData = $playlist->normalized_data;

        foreach ($normalizedData as $channelData) {
            $channel = Channel::updateOrCreate(
                [
                    'iptv_id' => $playlist->iptv_id,
                    'category_id' => $channelData['category_id'],
                    'name' => $channelData['name'],
                ],
                [
                    'stream_url' => $channelData['stream_url'],
                    'logo_url' => $channelData['logo_url'] ?? null,
                    'epg_channel_id' => $channelData['tvg_id'] ?? null,
                    'stream_type' => StreamType::LIVE,
                    'is_active' => true,
                    'metadata' => [
                        'tvg_name' => $channelData['tvg_name'] ?? null,
                    ],
                ]
            );

            $syncedCount++;
        }

        // Update category counts
        $this->updateCategoryCounts($playlist->iptv_id);

        return $syncedCount;
    }

    /**
     * Update channel counts for categories
     */
    protected function updateCategoryCounts(int $iptvId): void
    {
        $categories = Category::where('iptv_id', $iptvId)->get();

        foreach ($categories as $category) {
            $count = Channel::where('category_id', $category->id)
                ->where('is_active', true)
                ->count();

            $category->update(['channels_count' => $count]);
        }
    }

    /**
     * Load and sync M3U playlist from URL
     */
    public function loadAndSync(IPTV $iptv, string $url): array
    {
        // Parse M3U from URL
        $channels = $this->loadFromUrl($url);

        // Normalize and save
        $playlist = $this->normalizeAndSave($iptv, $channels);

        // Sync channels
        $syncedCount = $this->syncChannels($playlist);

        return [
            'playlist_id' => $playlist->id,
            'total_channels' => count($channels),
            'synced_channels' => $syncedCount,
            'checksum' => $playlist->checksum,
        ];
    }

    /**
     * Get cached playlist
     */
    public function getCached(IPTV $iptv): ?Playlist
    {
        $cacheKey = "playlist_iptv_{$iptv->id}";

        return Cache::remember($cacheKey, 3600, function () use ($iptv) {
            return Playlist::where('iptv_id', $iptv->id)
                ->where('expires_at', '>', now())
                ->first();
        });
    }

    /**
     * Check if playlist needs refresh
     */
    public function needsRefresh(Playlist $playlist): bool
    {
        if (!$playlist->expires_at) {
            return true;
        }

        return $playlist->expires_at->isPast();
    }

    /**
     * Validate playlist format
     */
    public function validateM3U(string $content): bool
    {
        $lines = explode("\n", $content);

        // M3U must start with #EXTM3U
        if (!isset($lines[0]) || trim($lines[0]) !== '#EXTM3U') {
            return false;
        }

        // Must have at least one EXTINF line
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#EXTINF:')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get playlist statistics
     */
    public function getStatistics(Playlist $playlist): array
    {
        $categories = Category::where('iptv_id', $playlist->iptv_id)->count();
        $channels = Channel::where('iptv_id', $playlist->iptv_id)->count();
        $activeChannels = Channel::where('iptv_id', $playlist->iptv_id)
            ->where('is_active', true)
            ->count();

        return [
            'total_categories' => $categories,
            'total_channels' => $channels,
            'active_channels' => $activeChannels,
            'inactive_channels' => $channels - $activeChannels,
            'expires_at' => $playlist->expires_at,
            'is_expired' => $this->needsRefresh($playlist),
            'checksum' => $playlist->checksum,
        ];
    }
}
