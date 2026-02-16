<?php

namespace App\Modules\IPTV\Contracts;

use App\Modules\IPTV\Enums\PlaylistProvider;

interface PlaylistParserInterface
{
    /**
     * Parse playlist from source
     *
     * @param string $source
     * @param PlaylistProvider $provider
     * @param array $credentials
     * @return array
     */
    public function parse(string $source, PlaylistProvider $provider, array $credentials = []): array;

    /**
     * Parse M3U format playlist
     *
     * @param string $content
     * @return array
     */
    public function parseM3U(string $content): array;

    /**
     * Parse Xtream Codes API response
     *
     * @param string $baseUrl
     * @param string $username
     * @param string $password
     * @return array
     */
    public function parseXtream(string $baseUrl, string $username, string $password): array;

    /**
     * Extract channel information from playlist
     *
     * @param array $playlist
     * @return array
     */
    public function extractChannels(array $playlist): array;

    /**
     * Extract categories from playlist
     *
     * @param array $playlist
     * @return array
     */
    public function extractCategories(array $playlist): array;

    /**
     * Validate playlist structure
     *
     * @param array $playlist
     * @return bool
     */
    public function validate(array $playlist): bool;

    /**
     * Get playlist metadata
     *
     * @param array $playlist
     * @return array
     */
    public function getMetadata(array $playlist): array;
}
