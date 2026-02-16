<?php

namespace App\Modules\History\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class History extends Model
{
    use HasUuids;

    protected $table = 'history';

    protected $fillable = [
        'user_id',
        'channel_id',
        'content_type',
        'watched_at',
    ];

    protected $casts = [
        'watched_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
