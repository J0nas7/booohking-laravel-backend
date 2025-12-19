<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
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
            'User_Remember_Token' => 'required|string|size:16',
            'New_User_Password'   => 'required|string|min:6|confirmed',
        ];
    }

    /**
     * Optionally, customize the error messages.
     */
    public function messages(): array
    {
        return [
            'User_Remember_Token.required' => 'The reset token is required.',
            'User_Remember_Token.size'     => 'The reset token must be 16 characters.',
            'New_User_Password.required'   => 'A new password is required.',
            'New_User_Password.min'        => 'The password must be at least 6 characters.',
            'New_User_Password.confirmed'  => 'The password confirmation does not match.',
        ];
    }
}
