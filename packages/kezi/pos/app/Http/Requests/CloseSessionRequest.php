<?php

namespace Kezi\Pos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloseSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'closing_cash' => ['required', 'integer', 'min:0'],
            'closing_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'closing_cash.required' => 'Please enter the closing cash amount.',
            'closing_cash.integer' => 'The closing cash must be a whole number (in minor currency units).',
            'closing_cash.min' => 'The closing cash cannot be negative.',
        ];
    }
}
