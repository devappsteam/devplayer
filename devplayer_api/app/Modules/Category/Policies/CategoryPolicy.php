<?php

namespace App\Modules\Category\Policies;

use App\Modules\User\Models\User;
use App\Modules\Category\Models\Category;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Category $category): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Category $category): bool
    {
        return true;
    }

    public function delete(User $user, Category $category): bool
    {
        return true;
    }

    public function restore(User $user, Category $category): bool
    {
        return true;
    }

    public function forceDelete(User $user, Category $category): bool
    {
        return true;
    }
}
