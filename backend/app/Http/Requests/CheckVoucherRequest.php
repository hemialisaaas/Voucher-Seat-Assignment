<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckVoucherRequest extends FormRequest
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
            'flightNumber' => ['required', 'string', 'max:10'],
            'date' => ['required', 'date_format:Y-m-d'],
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
            'flightNumber.required' => 'Flight number is required.',
            'flightNumber.max' => 'Flight number must not exceed 10 characters.',
            'date.required' => 'Date is required.',
            'date.date_format' => 'Date must be in YYYY-MM-DD format.',
        ];
    }
}
