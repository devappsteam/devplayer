<?php

namespace App\Modules\Channel\Enums;

enum StreamType: string
{
    case LIVE = 'live';
    case VOD = 'vod';
    case SERIES = 'series';

    /**
     * Get human readable label
     */
    public function label(): string
    {
        return match($this) {
            self::LIVE => 'Live TV',
            self::VOD => 'Video on Demand',
            self::SERIES => 'Series',
        };
    }

    /**
     * Check if type supports EPG
     */
    public function supportsEPG(): bool
    {
        return $this === self::LIVE;
    }

    /**
     * Check if type supports seasons/episodes
     */
    public function hasEpisodes(): bool
    {
        return $this === self::SERIES;
    }

    /**
     * Get icon name
     */
    public function icon(): string
    {
        return match($this) {
            self::LIVE => 'broadcast',
            self::VOD => 'movie',
            self::SERIES => 'tv',
        };
    }
}
