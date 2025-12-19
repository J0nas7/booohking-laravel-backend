<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
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
     * @return array
     */
    public function rules(): array
    {
        return [
            'User_Email' => 'required|string|email|exists:users,User_Email',
            'password' => 'required|string',
        ];
    }

    /**
     * Custom validation error messages (Optional)
     */
    public function messages(): array
    {
        return [
            'User_Email.required' => 'Please enter your email address.',
            'User_Email.email' => 'The email must be a valid email address.',
            'password.required' => 'Please enter your password.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new ValidationException($validator, response()->json([
            'error' => $validator->errors()->first(),
            'status' => 401,
        ], 401));
    }
}
