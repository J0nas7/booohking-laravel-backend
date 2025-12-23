<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProviderDTORequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'Provider_Name' => ['required', 'string', 'max:255'],
            'Service_ID' => ['required', 'exists:services,Service_ID'],
        ];
    }
}
