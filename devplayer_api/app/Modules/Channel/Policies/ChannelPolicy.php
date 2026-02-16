<?php

namespace App\Modules\Channel\Policies;

use App\Modules\User\Models\User;
use App\Modules\Channel\Models\Channel;

class ChannelPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Channel $channel): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Channel $channel): bool
    {
        return true;
    }

    public function delete(User $user, Channel $channel): bool
    {
        return true;
    }

    public function restore(User $user, Channel $channel): bool
    {
        return true;
    }

    public function forceDelete(User $user, Channel $channel): bool
    {
        return true;
    }
}
