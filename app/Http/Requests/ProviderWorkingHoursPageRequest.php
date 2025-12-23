<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProviderWorkingHoursPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page'        => ['sometimes', 'integer', 'min:1'],
            'perPage'     => ['sometimes', 'integer', 'min:1', 'max:100'],
            'Provider_ID' => ['sometimes', 'exists:providers,Provider_ID'],
        ];
    }

    public function validatedPagination(): array
    {
        return [
            'page'    => max((int) $this->query('page', 1), 1),
            'perPage' => max((int) $this->query('perPage', 10), 1),
        ];
    }

    public function validatedFilters(): array
    {
        return $this->only(['Provider_ID']);
    }
}
