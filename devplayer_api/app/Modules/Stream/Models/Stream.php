<?php

namespace App\Modules\Stream\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Core\Traits\HasUuid;
use App\Modules\Channel\Models\Channel;
use App\Modules\Stream\Enums\StreamProtocol;
use App\Modules\Stream\Enums\StreamQuality;
use App\Modules\Stream\Enums\ConnectionStatus;

class Stream extends Model
{
    use HasFactory, SoftDeletes, HasUuid;

    protected $fillable = [
        'uuid',
        'channel_id',
        'url',
        'protocol',
        'quality',
        'bitrate',
        'height',
        'width',
        'is_primary',
        'health_status',
        'last_check_at',
        'latency',
    ];

    protected $hidden = [
        'id',
    ];

    protected $casts = [
        'protocol' => StreamProtocol::class,
        'quality' => StreamQuality::class,
        'health_status' => ConnectionStatus::class,
        'bitrate' => 'integer',
        'height' => 'integer',
        'width' => 'integer',
        'is_primary' => 'boolean',
        'latency' => 'integer',
        'last_check_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the channel that owns the stream
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * Scope primary streams
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope healthy streams
     */
    public function scopeHealthy($query)
    {
        return $query->whereIn('health_status', [
            ConnectionStatus::ONLINE,
            ConnectionStatus::EXCELLENT,
        ]);
    }

    /**
     * Scope by quality
     */
    public function scopeByQuality($query, StreamQuality $quality)
    {
        return $query->where('quality', $quality);
    }

    /**
     * Check if stream is healthy
     */
    public function isHealthy(): bool
    {
        return $this->health_status->isUsable();
    }

    /**
     * Check if stream needs health check
     */
    public function needsHealthCheck(): bool
    {
        if (!$this->last_check_at) {
            return true;
        }

        return $this->last_check_at->addMinutes(5)->isPast();
    }

    /**
     * Update health status
     */
    public function updateHealthStatus(ConnectionStatus $status, ?int $latency = null): void
    {
        $this->update([
            'health_status' => $status,
            'latency' => $latency,
            'last_check_at' => now(),
        ]);
    }
}
