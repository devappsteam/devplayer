<?php

namespace App\Modules\Stream\Enums;

enum StreamQuality: string
{
    case AUTO = 'auto';
    case LOW = 'low';          // 360p
    case MEDIUM = 'medium';    // 480p
    case HIGH = 'high';        // 720p
    case FULL_HD = 'full_hd';  // 1080p
    case UHD_4K = 'uhd_4k';    // 2160p

    /**
     * Get human readable label
     */
    public function label(): string
    {
        return match($this) {
            self::AUTO => 'Auto',
            self::LOW => '360p (Low)',
            self::MEDIUM => '480p (Medium)',
            self::HIGH => '720p (High)',
            self::FULL_HD => '1080p (Full HD)',
            self::UHD_4K => '4K (Ultra HD)',
        };
    }

    /**
     * Get approximate bitrate in kbps
     */
    public function bitrate(): int
    {
        return match($this) {
            self::LOW => 800,
            self::MEDIUM => 1500,
            self::HIGH => 3000,
            self::FULL_HD => 6000,
            self::UHD_4K => 15000,
            self::AUTO => 0,
        };
    }

    /**
     * Get resolution height
     */
    public function height(): int
    {
        return match($this) {
            self::LOW => 360,
            self::MEDIUM => 480,
            self::HIGH => 720,
            self::FULL_HD => 1080,
            self::UHD_4K => 2160,
            self::AUTO => 0,
        };
    }

    /**
     * Get resolution width (16:9 aspect ratio)
     */
    public function width(): int
    {
        return match($this) {
            self::LOW => 640,
            self::MEDIUM => 854,
            self::HIGH => 1280,
            self::FULL_HD => 1920,
            self::UHD_4K => 3840,
            self::AUTO => 0,
        };
    }

    /**
     * Get quality from bitrate
     */
    public static function fromBitrate(int $bitrate): self
    {
        return match(true) {
            $bitrate >= 15000 => self::UHD_4K,
            $bitrate >= 6000 => self::FULL_HD,
            $bitrate >= 3000 => self::HIGH,
            $bitrate >= 1500 => self::MEDIUM,
            default => self::LOW,
        };
    }

    /**
     * Get quality from height
     */
    public static function fromHeight(int $height): self
    {
        return match(true) {
            $height >= 2160 => self::UHD_4K,
            $height >= 1080 => self::FULL_HD,
            $height >= 720 => self::HIGH,
            $height >= 480 => self::MEDIUM,
            default => self::LOW,
        };
    }

    /**
     * Get all qualities except auto
     */
    public static function allQualitiesExceptAuto(): array
    {
        return [
            self::LOW,
            self::MEDIUM,
            self::HIGH,
            self::FULL_HD,
            self::UHD_4K,
        ];
    }
}
