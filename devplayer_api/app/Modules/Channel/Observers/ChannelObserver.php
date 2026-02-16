<?php

namespace App\Modules\Channel\Observers;

use App\Modules\Channel\Models\Channel;
use Illuminate\Support\Facades\Cache;

class ChannelObserver
{
    /**
     * Handle the Channel "created" event.
     */
    public function created(Channel $channel): void
    {
        $this->updateCategoryCount($channel);
        $this->invalidateCache($channel);
    }

    /**
     * Handle the Channel "updated" event.
     */
    public function updated(Channel $channel): void
    {
        // If category changed, update both old and new category counts
        if ($channel->isDirty('category_id')) {
            $oldCategoryId = $channel->getOriginal('category_id');
            if ($oldCategoryId) {
                $this->updateCategoryCountById($oldCategoryId);
            }
        }

        $this->updateCategoryCount($channel);
        $this->invalidateCache($channel);
    }

    /**
     * Handle the Channel "deleted" event.
     */
    public function deleted(Channel $channel): void
    {
        $this->updateCategoryCount($channel);
        $this->invalidateCache($channel);

        // Delete associated streams
        $channel->streams()->delete();
    }

    /**
     * Handle the Channel "restored" event.
     */
    public function restored(Channel $channel): void
    {
        $this->updateCategoryCount($channel);
        $this->invalidateCache($channel);
    }

    /**
     * Handle the Channel "force deleted" event.
     */
    public function forceDeleted(Channel $channel): void
    {
        $this->updateCategoryCount($channel);
        $this->invalidateCache($channel);
    }

    /**
     * Update category channels count
     */
    protected function updateCategoryCount(Channel $channel): void
    {
        if ($channel->category) {
            $count = $channel->category->channels()
                ->where('is_active', true)
                ->count();

            $channel->category->updateQuietly(['channels_count' => $count]);
        }
    }

    /**
     * Update category count by ID
     */
    protected function updateCategoryCountById(int $categoryId): void
    {
        $category = \App\Modules\Category\Models\Category::find($categoryId);

        if ($category) {
            $count = $category->channels()
                ->where('is_active', true)
                ->count();

            $category->updateQuietly(['channels_count' => $count]);
        }
    }

    /**
     * Invalidate related caches
     */
    protected function invalidateCache(Channel $channel): void
    {
        Cache::forget("channel_{$channel->id}");
        Cache::forget("channel_{$channel->id}_streams");
        Cache::forget("category_{$channel->category_id}_channels");
        Cache::forget("iptv_{$channel->iptv_id}_channels");

        // Clear user-specific caches if channel is favorited
        if ($channel->favorites()->count() > 0) {
            $userIds = $channel->favorites()->pluck('user_id');
            foreach ($userIds as $userId) {
                Cache::forget("user_{$userId}_favorites");
            }
        }
    }
}
