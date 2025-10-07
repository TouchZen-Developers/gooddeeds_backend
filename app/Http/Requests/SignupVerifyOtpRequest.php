<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SignupVerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'otp' => 'required|string|digits:4',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email is required.',
            'email.email' => 'Please provide a valid email address.',
            'otp.required' => 'OTP is required.',
            'otp.digits' => 'OTP must be 4 digits.',
        ];
    }
}