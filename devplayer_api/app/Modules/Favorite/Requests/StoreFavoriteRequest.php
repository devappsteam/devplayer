<?php

namespace App\Modules\Favorite\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class StoreFavoriteRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = Auth::id();

        return [
            'channel_id' => [
                'required',
                'exists:channels,id',
                Rule::unique('favorites')->where(function ($query) use ($userId) {
                    return $query->where('user_id', $userId)
                        ->whereNull('deleted_at');
                }),
            ],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'channel_id' => 'channel',
        ];
    }

    public function messages(): array
    {
        return [
            'channel_id.required' => 'Channel is required',
            'channel_id.exists' => 'Invalid channel',
            'channel_id.unique' => 'Channel is already in your favorites',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => Auth::id(),
        ]);
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
