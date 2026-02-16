<?php

namespace App\Modules\Stream\Contracts;

use App\Modules\Stream\Enums\StreamProtocol;

interface StreamResolverInterface
{
    /**
     * Resolve stream URL and validate availability
     *
     * @param string $url
     * @return array{url: string, protocol: StreamProtocol, available: bool, qualities: array}
     */
    public function resolve(string $url): array;

    /**
     * Validate if stream is accessible
     *
     * @param string $url
     * @return bool
     */
    public function validate(string $url): bool;

    /**
     * Get available quality levels for stream
     *
     * @param string $url
     * @return array
     */
    public function getQualityLevels(string $url): array;

    /**
     * Test stream health and response time
     *
     * @param string $url
     * @return array{healthy: bool, latency: int, bitrate: int}
     */
    public function healthCheck(string $url): array;

    /**
     * Get fallback URLs for stream
     *
     * @param string $primaryUrl
     * @return array
     */
    public function getFallbackUrls(string $primaryUrl): array;

    /**
     * Extract stream metadata
     *
     * @param string $url
     * @return array
     */
    public function getMetadata(string $url): array;
}
