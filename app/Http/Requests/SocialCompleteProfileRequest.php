<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SocialCompleteProfileRequest extends FormRequest
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
            'phone_number' => 'required|string|max:20',
            'family_size' => 'required|integer|min:1|max:20',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'zip_code' => 'required|string|max:20',
            'affected_event' => 'required|string|max:255',
            'statement' => 'required|string|max:1000',
            'family_photo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'phone_number.required' => 'Phone number is required.',
            'phone_number.max' => 'Phone number may not be greater than 20 characters.',
            'family_size.required' => 'Family size is required.',
            'family_size.integer' => 'Family size must be a number.',
            'family_size.min' => 'Family size must be at least 1.',
            'family_size.max' => 'Family size may not be greater than 20.',
            'address.required' => 'Address is required.',
            'address.max' => 'Address may not be greater than 255 characters.',
            'city.required' => 'City is required.',
            'city.max' => 'City may not be greater than 100 characters.',
            'state.required' => 'State is required.',
            'state.max' => 'State may not be greater than 100 characters.',
            'zip_code.required' => 'Zip code is required.',
            'zip_code.max' => 'Zip code may not be greater than 20 characters.',
            'affected_event.required' => 'Affected event is required.',
            'affected_event.max' => 'Affected event may not be greater than 255 characters.',
            'statement.required' => 'Statement is required.',
            'statement.max' => 'Statement may not be greater than 1000 characters.',
            'family_photo.required' => 'Family photo is required.',
            'family_photo.image' => 'The uploaded file must be an image.',
            'family_photo.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, svg.',
            'family_photo.max' => 'The image may not be greater than 2MB.',
        ];
    }
}