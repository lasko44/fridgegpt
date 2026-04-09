<?php

namespace App\Http\Requests\Tokens;

use App\Models\TokenPackage;
use Illuminate\Foundation\Http\FormRequest;

class TokenCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'package_id' => ['required', 'integer', 'exists:token_packages,id'],
        ];
    }

    public function getPackage(): TokenPackage
    {
        return TokenPackage::active()->findOrFail($this->validated('package_id'));
    }
}
