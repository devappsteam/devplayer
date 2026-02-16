<?php

namespace App\Modules\History\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Channel\Models\Channel;

class History extends Model
{
    use HasUuids;

    protected $table = 'history';

    protected $fillable = [
        'user_id',
        'channel_id',
        'content_type',
        'episode_id',
        'episode_season',
        'episode_number',
        'episode_title',
        'episode_description',
        'episode_thumbnail',
        'episode_stream_url',
        'watched_at',
    ];

    protected $casts = [
        'watched_at' => 'datetime',
        'episode_id' => 'integer',
        'episode_season' => 'integer',
        'episode_number' => 'integer',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class, 'channel_id');
    }
}
