<?php

namespace App\Modules\IPTV\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Core\Traits\HasUuid;
use App\Models\User;
use App\Modules\IPTV\Enums\PlaylistProvider;
use App\Modules\IPTV\Enums\IPTVStatus;
use App\Modules\Category\Models\Category;
use App\Modules\Channel\Models\Channel;
use App\Modules\Playlist\Models\Playlist;

class IPTV extends Model
{
    use HasFactory, SoftDeletes, HasUuid;

    protected $fillable = [
        'uuid',
        'user_id',
        'name',
        'provider_type',
        'url',
        'username',
        'password',
        'status',
        'last_sync_at',
        'metadata',
    ];

    protected $hidden = [
        'id',
        'password',
    ];

    protected $casts = [
        'provider_type' => PlaylistProvider::class,
        'status' => IPTVStatus::class,
        'last_sync_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user that owns the IPTV
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the playlists for the IPTV
     */
    public function playlists(): HasMany
    {
        return $this->hasMany(Playlist::class, 'iptv_id');
    }

    /**
     * Get the categories for the IPTV
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'iptv_id');
    }

    /**
     * Get the channels for the IPTV
     */
    public function channels(): HasMany
    {
        return $this->hasMany(Channel::class, 'iptv_id');
    }

    /**
     * Scope active IPTV providers
     */
    public function scopeActive($query)
    {
        return $query->where('status', IPTVStatus::ACTIVE);
    }

    /**
     * Check if provider is active
     */
    public function isActive(): bool
    {
        return $this->status === IPTVStatus::ACTIVE;
    }

    /**
     * Get provider credentials
     */
    public function getCredentials(): array
    {
        return [
            'url' => $this->url,
            'username' => $this->username,
            'password' => $this->password,
        ];
    }
}
