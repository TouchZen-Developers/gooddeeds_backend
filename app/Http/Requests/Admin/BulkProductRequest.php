<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BulkProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'urls' => 'required|array|min:1|max:10',
            'urls.*' => 'required|url|max:1000',
            'category_id' => 'required|integer|exists:categories,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'urls.required' => 'At least one product URL is required.',
            'urls.array' => 'URLs must be provided as an array.',
            'urls.min' => 'At least one product URL is required.',
            'urls.max' => 'Maximum 10 URLs can be imported at once.',
            'urls.*.required' => 'Each URL is required.',
            'urls.*.url' => 'Each URL must be valid.',
            'category_id.required' => 'Category selection is required.',
            'category_id.exists' => 'Selected category does not exist.',
        ];
    }
}
