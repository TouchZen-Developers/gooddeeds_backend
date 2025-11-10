<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SocialTokenAuthRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'provider' => 'required|string|in:google,apple',
            'id_token' => 'required|string',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'provider.required' => 'Social provider is required.',
            'provider.in' => 'Invalid social provider. Must be either google or apple.',
            'id_token.required' => 'ID token is required.',
            'id_token.string' => 'ID token must be a valid string.',
        ];
    }
}

