<?php

namespace App\Modules\Channel\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChannelDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        $metadata = $this->metadata ?? [];

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'logo_url' => $this->logo_url,
            'stream_url' => $this->stream_url,
            'type' => $this->stream_type,
            'category_id' => $this->category_id,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'uuid' => $this->category->uuid,
                'name' => $this->category->name,
            ] : null,

            // Detalhes para filmes/séries
            'description' => $metadata['description'] ?? $metadata['plot'] ?? null,
            'plot' => $metadata['plot'] ?? null,
            'genre' => $metadata['genre'] ?? $metadata['genres'] ?? null,
            'director' => $metadata['director'] ?? null,
            'actors' => $metadata['actors'] ?? null,
            'duration' => $metadata['duration'] ?? null, // em minutos
            'rating' => $metadata['rating'] ?? null, // IMDb rating or similar
            'release_year' => $metadata['release_year'] ?? $metadata['year'] ?? null,
            'poster_url' => $metadata['poster_url'] ?? $metadata['poster'] ?? $this->logo_url,
            'backdrop_url' => $metadata['backdrop_url'] ?? $metadata['backdrop'] ?? null,
            'imdb_id' => $metadata['imdb_id'] ?? null,
            'tmdb_id' => $metadata['tmdb_id'] ?? null,

            // Info adicional
            'number' => $this->number,
            'is_active' => $this->is_active,
            'epg_channel_id' => $this->epg_channel_id,
            'external_id' => $this->external_id,

            // Streams disponíveis
            'streams' => $this->whenLoaded('streams', function () {
                return $this->streams->map(function ($stream) {
                    return [
                        'id' => $stream->id,
                        'uuid' => $stream->uuid,
                        'url' => $stream->url,
                        'quality' => $stream->quality?->value,
                        'is_primary' => $stream->is_primary,
                        'is_working' => $stream->is_working,
                    ];
                });
            }),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
