<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AffectedEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only allow admin users
        return $this->user() && $this->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $affectedEventId = $this->route('affected_event')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('affected_events', 'name')->ignore($affectedEventId),
            ],
            'is_active' => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The affected event name is required.',
            'name.unique' => 'An affected event with this name already exists.',
            'name.max' => 'The affected event name may not be greater than 255 characters.',
            'is_active.boolean' => 'The active status must be true or false.',
        ];
    }
}
