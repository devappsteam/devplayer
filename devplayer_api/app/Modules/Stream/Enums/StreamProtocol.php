<?php

namespace App\Modules\Stream\Enums;

enum StreamProtocol: string
{
    case HLS = 'hls';
    case DASH = 'dash';
    case RTMP = 'rtmp';
    case RTSP = 'rtsp';
    case HTTP_PROGRESSIVE = 'http_progressive';

    /**
     * Get human readable label
     */
    public function label(): string
    {
        return match($this) {
            self::HLS => 'HLS (HTTP Live Streaming)',
            self::DASH => 'MPEG-DASH',
            self::RTMP => 'RTMP',
            self::RTSP => 'RTSP',
            self::HTTP_PROGRESSIVE => 'HTTP Progressive',
        };
    }

    /**
     * Get file extension
     */
    public function extension(): string
    {
        return match($this) {
            self::HLS => 'm3u8',
            self::DASH => 'mpd',
            self::RTMP => 'rtmp',
            self::RTSP => 'rtsp',
            self::HTTP_PROGRESSIVE => 'mp4',
        };
    }

    /**
     * Check if protocol supports adaptive bitrate
     */
    public function supportsABR(): bool
    {
        return in_array($this, [self::HLS, self::DASH]);
    }

    /**
     * Check if protocol is web compatible
     */
    public function isWebCompatible(): bool
    {
        return in_array($this, [
            self::HLS,
            self::DASH,
            self::HTTP_PROGRESSIVE
        ]);
    }

    /**
     * Detect protocol from URL
     */
    public static function detect(string $url): ?self
    {
        if (str_contains($url, '.m3u8')) {
            return self::HLS;
        }

        if (str_contains($url, '.mpd')) {
            return self::DASH;
        }

        if (str_starts_with($url, 'rtmp://')) {
            return self::RTMP;
        }

        if (str_starts_with($url, 'rtsp://')) {
            return self::RTSP;
        }

        if (preg_match('/\.(mp4|webm|ogg)$/', $url)) {
            return self::HTTP_PROGRESSIVE;
        }

        return null;
    }

    /**
     * Get recommended buffer strategy
     */
    public function recommendedBufferLength(): int
    {
        return match($this) {
            self::HLS => 30,
            self::DASH => 30,
            self::RTMP => 10,
            self::RTSP => 5,
            self::HTTP_PROGRESSIVE => 60,
        };
    }
}
