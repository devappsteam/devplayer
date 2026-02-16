<?php

namespace App\Modules\Channel\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Channel\Repositories\Contracts\ChannelRepositoryInterface;
use App\Modules\Channel\Models\Channel;

class ChannelRepository extends BaseRepository implements ChannelRepositoryInterface
{
    public function __construct(Channel $model)
    {
        parent::__construct($model);
    }
    
}