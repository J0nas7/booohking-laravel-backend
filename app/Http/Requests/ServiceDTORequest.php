<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ServiceDTORequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'Service_Name' => ['required', 'string', 'max:255'],
            'User_ID' => ['required', 'exists:users,id'],
            'Service_DurationMinutes' => ['required', 'integer', 'min:1'],
            'Service_Description' => ['nullable', 'string'],
        ];
    }
}
