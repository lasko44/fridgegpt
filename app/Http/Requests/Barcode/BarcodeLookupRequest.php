<?php

namespace App\Http\Requests\Barcode;

use Illuminate\Foundation\Http\FormRequest;

class BarcodeLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Barcode lookup is free for everyone
    }

    public function rules(): array
    {
        return [
            'barcode' => ['required', 'string', 'regex:/^\d{8,13}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'barcode.regex' => 'Please enter a valid barcode (8-13 digits).',
        ];
    }
}
