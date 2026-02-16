<?php

namespace App\Modules\Playlist\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Playlist\Repositories\Contracts\PlaylistRepositoryInterface;
use App\Modules\Playlist\Models\Playlist;

class PlaylistRepository extends BaseRepository implements PlaylistRepositoryInterface
{
    public function __construct(Playlist $model)
    {
        parent::__construct($model);
    }
    
}