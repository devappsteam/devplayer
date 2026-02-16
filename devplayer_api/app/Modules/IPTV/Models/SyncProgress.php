<?php

namespace App\Modules\IPTV\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncProgress extends Model
{
    protected $table = 'sync_progress';

    protected $fillable = [
        'sync_id',
        'iptv_id',
        'type',
        'status',
        'current_step',
        'total_items',
        'processed_items',
        'message',
        'error_message',
        'metadata',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'total_items' => 'integer',
        'processed_items' => 'integer',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function iptv(): BelongsTo
    {
        return $this->belongsTo(IPTV::class);
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentageAttribute(): float
    {
        if (!$this->total_items || $this->total_items === 0) {
            return 0;
        }

        return round(($this->processed_items / $this->total_items) * 100, 2);
    }

    /**
     * Check if sync is in progress
     */
    public function isInProgress(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    /**
     * Check if sync is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if sync has failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
