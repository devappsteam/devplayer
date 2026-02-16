<?php

namespace App\Core\Contracts;

interface CacheManagerInterface
{
    /**
     * Store item in cache
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl Time to live in seconds
     * @return bool
     */
    public function set(string $key, mixed $value, int $ttl = 3600): bool;

    /**
     * Retrieve item from cache
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Check if key exists in cache
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Remove item from cache
     *
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool;

    /**
     * Clear all cache
     *
     * @return bool
     */
    public function flush(): bool;

    /**
     * Remember value with callback
     *
     * @param string $key
     * @param int $ttl
     * @param callable $callback
     * @return mixed
     */
    public function remember(string $key, int $ttl, callable $callback): mixed;

    /**
     * Cache playlist metadata
     *
     * @param string $playlistId
     * @param array $metadata
     * @param int $ttl
     * @return bool
     */
    public function cachePlaylist(string $playlistId, array $metadata, int $ttl = 3600): bool;

    /**
     * Get cached playlist
     *
     * @param string $playlistId
     * @return array|null
     */
    public function getPlaylist(string $playlistId): ?array;

    /**
     * Cache channel data
     *
     * @param string $channelId
     * @param array $data
     * @param int $ttl
     * @return bool
     */
    public function cacheChannel(string $channelId, array $data, int $ttl = 7200): bool;

    /**
     * Get cached channel
     *
     * @param string $channelId
     * @return array|null
     */
    public function getChannel(string $channelId): ?array;

    /**
     * Clear stale cache entries
     *
     * @return int Number of entries cleared
     */
    public function clearStale(): int;
}
