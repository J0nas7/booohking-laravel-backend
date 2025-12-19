<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendResetTokenRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // You can add authorization logic here. For now, we return true since it's public.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'User_Email' => 'required|email|exists:users,User_Email',
        ];
    }

    /**
     * Get the custom error messages for the validator.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'User_Email.required' => 'The email address is required.',
            'User_Email.email' => 'Please provide a valid email address.',
            'User_Email.exists' => 'Credentials does not exist.',
        ];
    }
}
