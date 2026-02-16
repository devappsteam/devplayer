<?php

namespace App\Modules\History\Services;

use App\Modules\History\Models\History;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\Paginator;

class HistoryService
{
    private const CACHE_TTL = 3600; // 1 hora

    public function addToHistory(string $userId, int $channelId, string $contentType = 'channel'): History
    {
        // Remover entrada antiga se existir
        History::where('user_id', $userId)
            ->where('channel_id', $channelId)
            ->delete();

        // Criar nova entrada
        $history = History::create([
            'user_id' => $userId,
            'channel_id' => $channelId,
            'content_type' => $contentType,
            'watched_at' => now(),
        ]);

        // Invalidar cache
        $this->invalidateCache($userId);

        return $history;
    }

    public function getLastWatched(string $userId, string $contentType = null)
    {
        $cacheKey = $this->getCacheKey($userId, 'last_watched', $contentType);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $contentType) {
            $query = History::where('user_id', $userId);

            if ($contentType) {
                $query->where('content_type', $contentType);
            }

            return $query->orderBy('watched_at', 'desc')->first();
        });
    }

    public function getHistory(string $userId, string $contentType = null, int $perPage = 15): Paginator
    {
        $cacheKey = $this->getCacheKey($userId, 'history', $contentType);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $contentType, $perPage) {
            $query = History::where('user_id', $userId);

            if ($contentType) {
                $query->where('content_type', $contentType);
            }

            return $query->orderBy('watched_at', 'desc')->paginate($perPage);
        });
    }

    public function clearHistory(string $userId): bool
    {
        History::where('user_id', $userId)->delete();
        $this->invalidateCache($userId);
        return true;
    }

    private function getCacheKey(string $userId, string $type, string $contentType = null): string
    {
        $key = "history:{$userId}:{$type}";

        if ($contentType) {
            $key .= ":{$contentType}";
        }

        return $key;
    }

    private function invalidateCache(string $userId): void
    {
        Cache::forget("history:{$userId}:last_watched");
        Cache::forget("history:{$userId}:last_watched:channel");
        Cache::forget("history:{$userId}:last_watched:movie");
        Cache::forget("history:{$userId}:last_watched:series");
        Cache::forget("history:{$userId}:history");
        Cache::forget("history:{$userId}:history:channel");
        Cache::forget("history:{$userId}:history:movie");
        Cache::forget("history:{$userId}:history:series");
    }
}
