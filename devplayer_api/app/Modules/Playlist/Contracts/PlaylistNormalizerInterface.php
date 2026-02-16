<?php

namespace App\Modules\Playlist\Contracts;

use App\Modules\IPTV\Enums\PlaylistProvider;

interface PlaylistNormalizerInterface
{
    /**
     * Normalize playlist to standard format
     *
     * @param array $rawPlaylist
     * @param PlaylistProvider $provider
     * @return array
     */
    public function normalize(array $rawPlaylist, PlaylistProvider $provider): array;

    /**
     * Convert to internal format
     *
     * @param array $playlist
     * @return array
     */
    public function toInternalFormat(array $playlist): array;

    /**
     * Group channels by categories
     *
     * @param array $channels
     * @return array
     */
    public function groupByCategories(array $channels): array;

    /**
     * Calculate playlist checksum
     *
     * @param array $playlist
     * @return string
     */
    public function calculateChecksum(array $playlist): string;

    /**
     * Merge playlists
     *
     * @param array $playlists
     * @return array
     */
    public function merge(array $playlists): array;
}
