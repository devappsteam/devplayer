<?php

namespace App\Modules\IPTV\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Exception;

class XtreamApiClient
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected ?array $serverInfo = null;
    protected int $timeout = 30;

    public function __construct(string $baseUrl, string $username, string $password)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Test connection and get server info
     */
    public function testConnection(): array
    {
        try {
            $response = Http::timeout($this->timeout)->get($this->buildUrl('player_api.php'), [
                'username' => $this->username,
                'password' => $this->password,
            ]);

            if ($response->successful()) {
                $this->serverInfo = $response->json();
                return [
                    'success' => true,
                    'server_info' => $this->serverInfo,
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to connect to server',
                'status' => $response->status(),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get server information
     */
    public function getServerInfo(): ?array
    {
        $cacheKey = "xtream_server_info_{$this->username}";

        return Cache::remember($cacheKey, 3600, function () {
            $response = Http::timeout($this->timeout)->get($this->buildUrl('player_api.php'), [
                'username' => $this->username,
                'password' => $this->password,
            ]);

            return $response->successful() ? $response->json() : null;
        });
    }

    /**
     * Get live streams
     */
    public function getLiveStreams(): array
    {
        return $this->request('get_live_streams');
    }

    /**
     * Get live categories
     */
    public function getLiveCategories(): array
    {
        return $this->request('get_live_categories');
    }

    /**
     * Get VOD streams
     */
    public function getVodStreams(): array
    {
        return $this->request('get_vod_streams');
    }

    /**
     * Get VOD categories
     */
    public function getVodCategories(): array
    {
        return $this->request('get_vod_categories');
    }

    /**
     * Get series
     */
    public function getSeries(): array
    {
        return $this->request('get_series');
    }

    /**
     * Get series categories
     */
    public function getSeriesCategories(): array
    {
        return $this->request('get_series_categories');
    }

    /**
     * Get EPG for specific stream
     */
    public function getEpg(int $streamId, int $limit = 50): array
    {
        return $this->request('get_simple_data_table', [
            'stream_id' => $streamId,
            'limit' => $limit,
        ]);
    }

    /**
     * Get short EPG (all streams)
     */
    public function getShortEpg(): array
    {
        return $this->request('get_short_epg');
    }

    /**
     * Get stream URL
     */
    public function getStreamUrl(int $streamId, string $extension = 'ts'): string
    {
        return "{$this->baseUrl}/live/{$this->username}/{$this->password}/{$streamId}.{$extension}";
    }

    /**
     * Get VOD stream URL
     */
    public function getVodUrl(int $vodId, string $extension = 'mp4'): string
    {
        return "{$this->baseUrl}/movie/{$this->username}/{$this->password}/{$vodId}.{$extension}";
    }

    /**
     * Alias for getVodUrl
     */
    public function getVodStreamUrl(int $vodId, string $extension = 'mp4'): string
    {
        return $this->getVodUrl($vodId, $extension);
    }

    /**
     * Get series stream URL
     */
    public function getSeriesUrl(int $seriesId): string
    {
        return "{$this->baseUrl}/series/{$this->username}/{$this->password}/{$seriesId}";
    }

    /**
     * Get M3U playlist URL
     */
    public function getM3uUrl(string $type = 'get.php', string $output = 'm3u8'): string
    {
        return "{$this->baseUrl}/{$type}?username={$this->username}&password={$this->password}&type={$output}";
    }

    /**
     * Make generic request to Xtream API
     */
    protected function request(string $action, array $params = []): array
    {
        try {
            $response = Http::timeout($this->timeout)->get($this->buildUrl('player_api.php'), array_merge([
                'username' => $this->username,
                'password' => $this->password,
                'action' => $action,
            ], $params));

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            throw new Exception("API request failed with status: {$response->status()}");
        } catch (Exception $e) {
            throw new Exception("Xtream API Error: {$e->getMessage()}");
        }
    }

    /**
     * Build full API URL
     */
    protected function buildUrl(string $endpoint): string
    {
        return "{$this->baseUrl}/{$endpoint}";
    }

    /**
     * Set request timeout
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Get credentials
     */
    public function getCredentials(): array
    {
        return [
            'base_url' => $this->baseUrl,
            'username' => $this->username,
            'password' => $this->password,
        ];
    }

    /**
     * Check if server is online
     */
    public function isOnline(): bool
    {
        try {
            $response = Http::timeout(5)->get($this->baseUrl);
            return $response->successful();
        } catch (Exception $e) {
            return false;
        }
    }
}
