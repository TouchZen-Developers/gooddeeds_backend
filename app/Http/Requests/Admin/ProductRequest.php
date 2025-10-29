<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
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
        $productId = $this->route('product')?->id;
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'url' => [
                $isUpdate ? 'sometimes' : 'required',
                'url',
                'max:1000',
                Rule::unique('products', 'url')->ignore($productId),
            ],
            'category_id' => [
                $isUpdate ? 'sometimes' : 'required',
                'integer',
                'exists:categories,id',
            ],
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:2000',
            'price' => 'sometimes|numeric|min:0|max:999999.99',
            'currency' => 'sometimes|string|size:3',
            'image_url' => 'sometimes|url|max:1000',
            'features' => 'sometimes|array',
            'features.*' => 'string|max:255',
            'specifications' => 'sometimes|array',
            'availability' => 'sometimes|string|max:50',
            'rating' => 'sometimes|numeric|min:0|max:5',
            'review_count' => 'sometimes|integer|min:0',
            'brand' => 'sometimes|string|max:100',
            'model' => 'sometimes|string|max:100',
            'is_active' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'url.required' => 'Product URL is required.',
            'url.url' => 'Please provide a valid URL.',
            'url.unique' => 'This product URL has already been added.',
            'category_id.required' => 'Category selection is required.',
            'category_id.exists' => 'Selected category does not exist.',
            'price.numeric' => 'Price must be a valid number.',
            'price.min' => 'Price cannot be negative.',
            'rating.min' => 'Rating cannot be less than 0.',
            'rating.max' => 'Rating cannot be more than 5.',
        ];
    }
}
