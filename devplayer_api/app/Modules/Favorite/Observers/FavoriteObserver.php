<?php

namespace App\Modules\Favorite\Observers;

use App\Modules\Favorite\Models\Favorite;

class FavoriteObserver
{
    /**
     * Handle the Favorite "creating" event.
     */
    public function creating(Favorite $favorite): void
    {
        //
    }

    /**
     * Handle the Favorite "created" event.
     */
    public function created(Favorite $favorite): void
    {
        //
    }

    /**
     * Handle the Favorite "updating" event.
     */
    public function updating(Favorite $favorite): void
    {
        //
    }

    /**
     * Handle the Favorite "updated" event.
     */
    public function updated(Favorite $favorite): void
    {
        //
    }

    /**
     * Handle the Favorite "deleting" event.
     */
    public function deleting(Favorite $favorite): void
    {
        //
    }

    /**
     * Handle the Favorite "deleted" event.
     */
    public function deleted(Favorite $favorite): void
    {
        //
    }

    /**
     * Handle the Favorite "restoring" event.
     */
    public function restoring(Favorite $favorite): void
    {
        //
    }

    /**
     * Handle the Favorite "restored" event.
     */
    public function restored(Favorite $favorite): void
    {
        //
    }

    /**
     * Handle the Favorite "force deleted" event.
     */
    public function forceDeleted(Favorite $favorite): void
    {
        //
    }
}
