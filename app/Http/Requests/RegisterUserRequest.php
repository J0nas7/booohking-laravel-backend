<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterUserRequest extends FormRequest
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
            'acceptTerms'   => 'required|accepted',
            'User_Email'    => 'required|email|unique:users,User_Email',
            'User_Password' => 'required|min:6|confirmed',
            'User_Name'     => 'required|string|max:255',
            'User_Role'     => 'nullable|in:ROLE_ADMIN,ROLE_USER',
        ];
    }

    /**
     * Customize the error messages.
     */
    public function messages(): array
    {
        return [
            'acceptTerms.required' => 'You must accept the terms and conditions.',
            'User_Email.required'  => 'The email field is required.',
            'User_Email.email'     => 'The email must be a valid email address.',
            'User_Email.unique'    => 'This email is already registered.',
            'User_Password.required' => 'The password field is required.',
            'User_Password.min'      => 'The password must be at least 6 characters.',
            'User_Password.confirmed' => 'The password confirmation does not match.',
            'User_Name.required'   => 'The name field is required.',
            'User_Name.max'        => 'The name may not be greater than 255 characters.',
            'User_Role.in'         => 'The selected role is invalid.',
        ];
    }
}
