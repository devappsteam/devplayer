<?php

namespace App\Modules\Stream\Enums;

enum ConnectionStatus: string
{
    case ONLINE = 'online';
    case OFFLINE = 'offline';
    case SLOW = 'slow';
    case UNSTABLE = 'unstable';
    case EXCELLENT = 'excellent';

    /**
     * Get human readable label
     */
    public function label(): string
    {
        return match($this) {
            self::ONLINE => 'Online',
            self::OFFLINE => 'Offline',
            self::SLOW => 'Slow Connection',
            self::UNSTABLE => 'Unstable',
            self::EXCELLENT => 'Excellent',
        };
    }

    /**
     * Get color for UI
     */
    public function color(): string
    {
        return match($this) {
            self::EXCELLENT => 'green',
            self::ONLINE => 'blue',
            self::SLOW => 'yellow',
            self::UNSTABLE => 'orange',
            self::OFFLINE => 'red',
        };
    }

    /**
     * Get status from throughput (in kbps)
     */
    public static function fromThroughput(float $throughput): self
    {
        return match(true) {
            $throughput <= 0 => self::OFFLINE,
            $throughput < 500 => self::SLOW,
            $throughput < 2000 => self::UNSTABLE,
            $throughput >= 5000 => self::EXCELLENT,
            default => self::ONLINE,
        };
    }

    /**
     * Check if connection is usable
     */
    public function isUsable(): bool
    {
        return !in_array($this, [self::OFFLINE]);
    }

    /**
     * Check if connection is stable
     */
    public function isStable(): bool
    {
        return in_array($this, [self::ONLINE, self::EXCELLENT]);
    }

    /**
     * Get recommended quality for this connection
     */
    public function recommendedQuality(): StreamQuality
    {
        return match($this) {
            self::EXCELLENT => StreamQuality::FULL_HD,
            self::ONLINE => StreamQuality::HIGH,
            self::SLOW => StreamQuality::MEDIUM,
            self::UNSTABLE => StreamQuality::LOW,
            self::OFFLINE => StreamQuality::LOW,
        };
    }

    /**
     * Get recommended buffer length in seconds
     */
    public function recommendedBufferLength(): int
    {
        return match($this) {
            self::EXCELLENT => 20,
            self::ONLINE => 30,
            self::SLOW => 45,
            self::UNSTABLE => 60,
            self::OFFLINE => 60,
        };
    }
}
