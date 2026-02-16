<?php

namespace App\Modules\IPTV\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreIPTVRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'provider_type' => ['required', Rule::in(['xtream', 'm3u', 'm3u_url'])],
            'url' => ['required', 'url', 'max:2048'],
            'username' => ['nullable', 'required_if:provider_type,xtream', 'string', 'max:255'],
            'password' => ['nullable', 'required_if:provider_type,xtream', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'error'])],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function attributes(): array
    {
        return [
            'provider_type' => 'provider type',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Provider name is required',
            'provider_type.required' => 'Provider type is required',
            'provider_type.in' => 'Invalid provider type',
            'url.required' => 'Provider URL is required',
            'url.url' => 'Invalid URL format',
            'username.required_if' => 'Username is required for Xtream provider',
            'password.required_if' => 'Password is required for Xtream provider',
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
