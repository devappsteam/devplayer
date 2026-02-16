<?php

namespace App\Modules\Channel\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreChannelRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'exists:categories,id'],
            'iptv_id' => ['required', 'exists:i_p_t_v_s,id'],
            'name' => ['required', 'string', 'max:255'],
            'external_id' => ['nullable', 'string', 'max:255'],
            'logo_url' => ['nullable', 'url', 'max:2048'],
            'stream_url' => ['required', 'string', 'max:2048'],
            'stream_type' => ['required', Rule::in(['live', 'vod', 'series'])],
            'epg_channel_id' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function attributes(): array
    {
        return [
            'iptv_id' => 'IPTV provider',
            'stream_url' => 'stream URL',
            'stream_type' => 'stream type',
        ];
    }

    public function messages(): array
    {
        return [
            'iptv_id.required' => 'IPTV provider is required',
            'iptv_id.exists' => 'Invalid IPTV provider',
            'name.required' => 'Channel name is required',
            'stream_url.required' => 'Stream URL is required',
            'stream_type.required' => 'Stream type is required',
            'stream_type.in' => 'Invalid stream type',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 422)
        );
    }
}
