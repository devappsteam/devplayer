<?php

namespace App\Modules\Channel\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEpisodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'channel_id' => 'sometimes|integer|exists:channels,id',
            'season' => 'sometimes|integer|min:1',
            'episode' => 'sometimes|integer|min:1',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'plot' => 'nullable|string',
            'aired_date' => 'nullable|date',
            'duration' => 'nullable|integer|min:1',
            'thumbnail_url' => 'nullable|url',
            'external_id' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
            'is_active' => 'boolean',
        ];
    }
}
