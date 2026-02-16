<?php

namespace App\Modules\Category\Observers;

use App\Modules\Category\Models\Category;
use Illuminate\Support\Facades\Cache;

class CategoryObserver
{
    /**
     * Handle the Category "created" event.
     */
    public function created(Category $category): void
    {
        $this->invalidateCache($category);
    }

    /**
     * Handle the Category "updated" event.
     */
    public function updated(Category $category): void
    {
        $this->invalidateCache($category);
    }

    /**
     * Handle the Category "deleting" event.
     */
    public function deleting(Category $category): void
    {
        // Set channels to inactive instead of deleting them
        $category->channels()->update(['is_active' => false]);
    }

    /**
     * Handle the Category "deleted" event.
     */
    public function deleted(Category $category): void
    {
        $this->invalidateCache($category);
    }

    /**
     * Handle the Category "restored" event.
     */
    public function restored(Category $category): void
    {
        $this->invalidateCache($category);

        // Reactivate channels
        $category->channels()->update(['is_active' => true]);
    }

    /**
     * Handle the Category "force deleted" event.
     */
    public function forceDeleted(Category $category): void
    {
        $this->invalidateCache($category);
    }

    /**
     * Invalidate related caches
     */
    protected function invalidateCache(Category $category): void
    {
        Cache::forget("category_{$category->id}_channels");
        Cache::forget("iptv_{$category->iptv_id}_categories");
        Cache::forget("iptv_{$category->iptv_id}_stats");
    }
}
