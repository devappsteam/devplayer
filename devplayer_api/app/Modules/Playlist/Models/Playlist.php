<?php

namespace App\Modules\Playlist\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Core\Traits\HasUuid;
use App\Modules\IPTV\Models\IPTV;
use App\Modules\IPTV\Enums\PlaylistProvider;

class Playlist extends Model
{
    use HasFactory, SoftDeletes, HasUuid;

    protected $fillable = [
        'uuid',
        'iptv_id',
        'provider_type',
        'raw_data',
        'normalized_data',
        'channels_count',
        'categories_count',
        'expires_at',
        'checksum',
    ];

    protected $hidden = [
        'id',
        'raw_data',
    ];

    protected $casts = [
        'provider_type' => PlaylistProvider::class,
        'normalized_data' => 'array',
        'channels_count' => 'integer',
        'categories_count' => 'integer',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the IPTV provider that owns the playlist
     */
    public function iptv(): BelongsTo
    {
        return $this->belongsTo(IPTV::class, 'iptv_id');
    }

    /**
     * Scope valid playlists (not expired)
     */
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now())
            ->orWhereNull('expires_at');
    }

    /**
     * Check if playlist is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if playlist needs refresh
     */
    public function needsRefresh(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->subMinutes(5)->isPast();
    }
}
