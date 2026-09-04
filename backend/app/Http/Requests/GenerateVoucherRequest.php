<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateVoucherRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'id' => ['required', 'string', 'max:50'],
            'flightNumber' => ['required', 'string', 'max:10'],
            'date' => ['required', 'date_format:Y-m-d'],
            'aircraft' => ['required', 'string', Rule::in(['ATR', 'Airbus 320', 'Boeing 737 Max'])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Crew name is required.',
            'id.required' => 'Crew ID is required.',
            'flightNumber.required' => 'Flight number is required.',
            'date.required' => 'Flight date is required.',
            'date.date_format' => 'Date must be in YYYY-MM-DD format.',
            'aircraft.required' => 'Aircraft type is required.',
            'aircraft.in' => 'Aircraft type must be one of: ATR, Airbus 320, Boeing 737 Max.',
        ];
    }
}
