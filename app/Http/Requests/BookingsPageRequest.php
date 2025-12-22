<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookingsPageRequest extends FormRequest
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
            'page' => ['sometimes', 'integer', 'min:1'],
            'perPage' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function validatedPagination(): array
    {
        return [
            'page' => max((int) $this->query('page', 1), 1),
            'perPage' => max((int) $this->query('perPage', 10), 1),
        ];
    }
}
