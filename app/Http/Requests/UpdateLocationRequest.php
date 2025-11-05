<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLocationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'latitude' => [
                'required',
                'numeric',
                'min:-90',
                'max:90',
                'regex:/^-?([0-8]?[0-9](\.\d{1,8})?|90(\.0{1,8})?)$/', // Validates decimal precision
            ],
            'longitude' => [
                'required',
                'numeric',
                'min:-180',
                'max:180',
                'regex:/^-?((1[0-7][0-9]|[0-9]{1,2})(\.\d{1,8})?|180(\.0{1,8})?)$/', // Validates decimal precision
            ],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'latitude.required' => 'Latitude is required.',
            'latitude.numeric' => 'Latitude must be a valid number.',
            'latitude.min' => 'Latitude must be between -90 and 90 degrees.',
            'latitude.max' => 'Latitude must be between -90 and 90 degrees.',
            'latitude.regex' => 'Latitude format is invalid. Must be a decimal number with up to 8 decimal places.',
            
            'longitude.required' => 'Longitude is required.',
            'longitude.numeric' => 'Longitude must be a valid number.',
            'longitude.min' => 'Longitude must be between -180 and 180 degrees.',
            'longitude.max' => 'Longitude must be between -180 and 180 degrees.',
            'longitude.regex' => 'Longitude format is invalid. Must be a decimal number with up to 8 decimal places.',
        ];
    }
}

