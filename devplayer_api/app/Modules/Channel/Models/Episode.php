<?php

namespace App\Modules\Channel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Core\Traits\HasUuid;

class Episode extends Model
{
    use HasFactory, SoftDeletes, HasUuid;

    protected $table = 'episodes';

    protected $fillable = [
        'uuid',
        'channel_id',
        'season',
        'episode',
        'name',
        'description',
        'plot',
        'aired_date',
        'duration',
        'thumbnail_url',
        'external_id',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'season' => 'integer',
        'episode' => 'integer',
        'duration' => 'integer',
        'aired_date' => 'date',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the channel that owns the episode
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * Scope active episodes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by season
     */
    public function scopeBySeason($query, int $season)
    {
        return $query->where('season', $season);
    }

    /**
     * Get full episode title (e.g., "S01E05 - Episode Name")
     */
    public function getFullTitleAttribute(): string
    {
        return sprintf(
            'S%02dE%02d - %s',
            $this->season,
            $this->episode,
            $this->name
        );
    }
}
