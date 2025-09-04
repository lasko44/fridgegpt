<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class VariationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $ingredients = $this->input('ingredients', []);
        //only return the name of each ingredient
        $ingredientNames = array_map(function ($ingredient) {
            return $ingredient['name'] ?? '';
        }, $ingredients);

        $this->merge(['ingredients' => $ingredientNames]);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        //check if auth user is premium
        return $this->user() && $this->user()->is_subscribed;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        dd($this->all());
        //dump the request after prepare for validation
        return [
            'ingredients.*' => ['required', 'string', 'distinct', 'min:1'],

        ];
    }
}
