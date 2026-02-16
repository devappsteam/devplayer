<?php

namespace App\Modules\Playlist\Policies;

use App\Modules\User\Models\User;
use App\Modules\Playlist\Models\Playlist;

class PlaylistPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Playlist $playlist): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Playlist $playlist): bool
    {
        return true;
    }

    public function delete(User $user, Playlist $playlist): bool
    {
        return true;
    }

    public function restore(User $user, Playlist $playlist): bool
    {
        return true;
    }

    public function forceDelete(User $user, Playlist $playlist): bool
    {
        return true;
    }
}
