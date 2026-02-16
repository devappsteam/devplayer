<?php

namespace App\Modules\IPTV\Enums;

enum IPTVStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case ERROR = 'error';

    /**
     * Get human readable label
     */
    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::ERROR => 'Error',
        };
    }

    /**
     * Get color for UI
     */
    public function color(): string
    {
        return match($this) {
            self::ACTIVE => 'green',
            self::INACTIVE => 'gray',
            self::ERROR => 'red',
        };
    }

    /**
     * Check if provider is usable
     */
    public function isUsable(): bool
    {
        return $this === self::ACTIVE;
    }
}
