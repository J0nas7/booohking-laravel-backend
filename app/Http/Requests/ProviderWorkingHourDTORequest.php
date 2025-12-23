<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProviderWorkingHourDTORequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'Provider_ID'     => ['required', 'exists:providers,Provider_ID'],
            'PWH_DayOfWeek'   => ['required', 'integer', 'min:0', 'max:6'],
            'PWH_StartTime'   => ['required', 'date_format:H:i'],
            'PWH_EndTime'     => ['required', 'date_format:H:i', 'after:PWH_StartTime'],
        ];
    }
}
