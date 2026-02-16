<?php

namespace App\Modules\Channel\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EpisodeResource extends JsonResource
{
    public function toArray($request): array
    {
        $metadata = $this->metadata ?? [];

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'channel_id' => $this->channel_id,
            'season' => $this->season,
            'episode' => $this->episode,
            'name' => $this->name,
            'full_title' => $this->full_title,
            'description' => $this->description,
            'plot' => $this->plot ?? $metadata['plot'] ?? null,
            'aired_date' => $this->aired_date?->format('Y-m-d'),
            'duration' => $this->duration,
            'thumbnail_url' => $this->thumbnail_url,
            'external_id' => $this->external_id,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
