<?php

namespace App\Modules\IPTV\Enums;

enum PlaylistProvider: string
{
    case XTREAM = 'xtream';
    case M3U = 'm3u';
    case M3U_URL = 'm3u_url';

    /**
     * Get human readable label
     */
    public function label(): string
    {
        return match($this) {
            self::XTREAM => 'Xtream Codes API',
            self::M3U => 'M3U File',
            self::M3U_URL => 'M3U URL',
        };
    }

    /**
     * Check if provider requires authentication
     */
    public function requiresAuth(): bool
    {
        return $this === self::XTREAM;
    }

    /**
     * Check if provider supports EPG
     */
    public function supportsEPG(): bool
    {
        return in_array($this, [self::XTREAM, self::M3U_URL]);
    }

    /**
     * Check if provider supports categories
     */
    public function supportsCategories(): bool
    {
        return $this === self::XTREAM;
    }

    /**
     * Get required fields for provider
     */
    public function requiredFields(): array
    {
        return match($this) {
            self::XTREAM => ['url', 'username', 'password'],
            self::M3U => ['file_path'],
            self::M3U_URL => ['url'],
        };
    }

    /**
     * Get API endpoint for provider
     */
    public function getEndpoint(string $baseUrl, string $action): ?string
    {
        if ($this !== self::XTREAM) {
            return null;
        }

        return match($action) {
            'live' => "{$baseUrl}/player_api.php?action=get_live_streams",
            'vod' => "{$baseUrl}/player_api.php?action=get_vod_streams",
            'series' => "{$baseUrl}/player_api.php?action=get_series",
            'categories_live' => "{$baseUrl}/player_api.php?action=get_live_categories",
            'categories_vod' => "{$baseUrl}/player_api.php?action=get_vod_categories",
            'categories_series' => "{$baseUrl}/player_api.php?action=get_series_categories",
            default => null,
        };
    }
}
