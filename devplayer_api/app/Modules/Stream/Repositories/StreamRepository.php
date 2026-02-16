<?php

namespace App\Modules\Stream\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Stream\Repositories\Contracts\StreamRepositoryInterface;
use App\Modules\Stream\Models\Stream;

class StreamRepository extends BaseRepository implements StreamRepositoryInterface
{
    public function __construct(Stream $model)
    {
        parent::__construct($model);
    }
    
}