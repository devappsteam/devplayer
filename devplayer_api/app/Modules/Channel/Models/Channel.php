<?php

namespace App\Modules\Channel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Core\Traits\HasUuid;
use App\Modules\Category\Models\Category;
use App\Modules\IPTV\Models\IPTV;
use App\Modules\Stream\Models\Stream;
use App\Modules\Favorite\Models\Favorite;
use App\Modules\Channel\Enums\StreamType;

class Channel extends Model
{
    use HasFactory, SoftDeletes, HasUuid;

    protected $fillable = [
        'uuid',
        'category_id',
        'iptv_id',
        'name',
        'external_id',
        'logo_url',
        'stream_url',
        'stream_type',
        'epg_channel_id',
        'number',
        'is_active',
        'metadata',
    ];

    // Remove 'id' from hidden to expose numeric ID for API
    protected $hidden = [];

    protected $casts = [
        'stream_type' => StreamType::class,
        'number' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the category that owns the channel
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the IPTV provider that owns the channel
     */
    public function iptv(): BelongsTo
    {
        return $this->belongsTo(IPTV::class, 'iptv_id');
    }

    /**
     * Get the streams for the channel
     */
    public function streams(): HasMany
    {
        return $this->hasMany(Stream::class);
    }

    /**
     * Get the favorites for the channel
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Get episodes for the channel (for series)
     */
    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class);
    }

    /**
     * Scope active channels
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by stream type
     */
    public function scopeOfType($query, StreamType $type)
    {
        return $query->where('stream_type', $type);
    }

    /**
     * Scope live channels
     */
    public function scopeLive($query)
    {
        return $query->where('stream_type', StreamType::LIVE);
    }

    /**
     * Get primary stream
     */
    public function getPrimaryStreamAttribute()
    {
        return $this->streams()->where('is_primary', true)->first();
    }

    /**
     * Check if channel is favorited by user
     */
    public function isFavoritedBy(int $userId): bool
    {
        return $this->favorites()->where('user_id', $userId)->exists();
    }
}
