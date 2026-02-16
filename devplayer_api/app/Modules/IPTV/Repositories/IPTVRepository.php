<?php

namespace App\Modules\IPTV\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\IPTV\Repositories\Contracts\IPTVRepositoryInterface;
use App\Modules\IPTV\Models\IPTV;

class IPTVRepository extends BaseRepository implements IPTVRepositoryInterface
{
    public function __construct(IPTV $model)
    {
        parent::__construct($model);
    }
    
}