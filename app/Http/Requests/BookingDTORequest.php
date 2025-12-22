<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookingDTORequest extends FormRequest
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
            'User_ID' => ['required', 'exists:users,id'],
            'Provider_ID' => ['required', 'exists:providers,Provider_ID'],
            'Service_ID' => ['required', 'exists:services,Service_ID'],
            'Booking_StartAt' => ['required', 'date', 'after:now'],
            'Booking_EndAt' => ['required', 'date', 'after:Booking_StartAt'],
            'Booking_Status' => ['sometimes', 'in:booked,cancelled'],
            'Booking_CancelledAt' => ['sometimes', 'date'],
        ];
    }
}
