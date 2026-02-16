<?php

namespace App\Modules\IPTV\Policies;

use App\Modules\User\Models\User;
use App\Modules\IPTV\Models\IPTV;

class IPTVPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, IPTV $iPTV): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, IPTV $iPTV): bool
    {
        return true;
    }

    public function delete(User $user, IPTV $iPTV): bool
    {
        return true;
    }

    public function restore(User $user, IPTV $iPTV): bool
    {
        return true;
    }

    public function forceDelete(User $user, IPTV $iPTV): bool
    {
        return true;
    }
}
