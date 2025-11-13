<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class BeneficiarySignupRequest extends FormRequest
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
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'required|string|max:20',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'family_size' => 'nullable|integer|min:1',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:20',
            'affected_event' => 'nullable|string|max:255',
            'statement' => 'nullable|string|max:2000',
            'family_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:5120', // 5MB max
            'identity_proof' => 'nullable|image|mimes:jpeg,png,jpg,webp,pdf|max:5120', // 5MB max
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
            'phone_number.required' => 'Phone number is required.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'family_size.integer' => 'Family size must be a number.',
            'family_size.min' => 'Family size must be at least 1.',
            'family_photo.image' => 'Family photo must be an image file.',
            'family_photo.mimes' => 'Family photo must be a JPEG, PNG, JPG, WebP, or GIF file.',
            'family_photo.max' => 'Family photo must not be larger than 5MB.',
            'identity_proof.image' => 'Identity proof must be an image or PDF file.',
            'identity_proof.mimes' => 'Identity proof must be a JPEG, PNG, JPG, WebP, or PDF file.',
            'identity_proof.max' => 'Identity proof must not be larger than 5MB.',
            'statement.max' => 'Statement must not exceed 2000 characters.',
        ];
    }
}
