<?php

namespace App\Modules\Category\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Core\Traits\HasUuid;
use App\Modules\IPTV\Models\IPTV;
use App\Modules\Channel\Models\Channel;

class Category extends Model
{
    use HasFactory, SoftDeletes, HasUuid;

    protected $fillable = [
        'uuid',
        'iptv_id',
        'name',
        'type',
        'external_id',
        'order',
        'channels_count',
    ];

    protected $hidden = [
        'id',
    ];

    protected $casts = [
        'order' => 'integer',
        'channels_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the IPTV provider that owns the category
     */
    public function iptv(): BelongsTo
    {
        return $this->belongsTo(IPTV::class, 'iptv_id');
    }

    /**
     * Get the channels for the category
     */
    public function channels(): HasMany
    {
        return $this->hasMany(Channel::class);
    }

    /**
     * Scope ordered categories
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
