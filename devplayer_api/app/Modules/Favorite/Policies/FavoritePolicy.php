<?php

namespace App\Modules\Favorite\Policies;

use App\Modules\User\Models\User;
use App\Modules\Favorite\Models\Favorite;

class FavoritePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Favorite $favorite): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Favorite $favorite): bool
    {
        return true;
    }

    public function delete(User $user, Favorite $favorite): bool
    {
        return true;
    }

    public function restore(User $user, Favorite $favorite): bool
    {
        return true;
    }

    public function forceDelete(User $user, Favorite $favorite): bool
    {
        return true;
    }
}
