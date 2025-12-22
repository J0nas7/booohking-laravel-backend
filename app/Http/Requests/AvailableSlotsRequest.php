<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AvailableSlotsRequest extends FormRequest
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
            'service_id' => ['sometimes', 'integer', 'exists:services,Service_ID'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'perPage' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'daysAhead' => ['sometimes', 'integer', 'min:1', 'max:365'],
            'slotMinutes' => ['sometimes', 'integer', 'min:1', 'max:480'], // 8 hours max per slot
        ];
    }

    public function validatedWithDefaults(): array
    {
        return array_merge($this->validated(), [
            'service_id' => $this->serviceId(),
            'daysAhead' => $this->daysAhead(),
            'slotMinutes' => $this->slotMinutes(),
            'page' => $this->query('page', 1),
            'perPage' => $this->query('perPage', 20),
        ]);
    }

    /**
     * Return validated and normalized pagination data
     */
    public function validatedPagination(): array
    {
        return [
            'page' => max((int) $this->query('page', 1), 1),
            'perPage' => max((int) $this->query('perPage', 20), 1),
        ];
    }

    /**
     * Return service ID if provided
     */
    public function serviceId(): ?int
    {
        return $this->input('service_id');
    }

    public function daysAhead(): int
    {
        return (int) $this->input('daysAhead', 30);
    }

    public function slotMinutes(): int
    {
        return (int) $this->input('slotMinutes', 30);
    }
}
