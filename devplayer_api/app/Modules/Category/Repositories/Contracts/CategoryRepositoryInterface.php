<?php

namespace App\Modules\Category\Repositories\Contracts;

use App\Core\Contracts\RepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

interface CategoryRepositoryInterface extends RepositoryInterface
{
    /**
     * Get categories by type with pagination
     *
     * @param string $type
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByType(string $type, int $perPage = 15): LengthAwarePaginator;
}
