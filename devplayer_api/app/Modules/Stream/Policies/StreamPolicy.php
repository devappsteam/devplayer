<?php

namespace App\Modules\Stream\Policies;

use App\Modules\User\Models\User;
use App\Modules\Stream\Models\Stream;

class StreamPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Stream $stream): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Stream $stream): bool
    {
        return true;
    }

    public function delete(User $user, Stream $stream): bool
    {
        return true;
    }

    public function restore(User $user, Stream $stream): bool
    {
        return true;
    }

    public function forceDelete(User $user, Stream $stream): bool
    {
        return true;
    }
}
