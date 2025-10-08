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
        //dump the request after prepare for validation
        return [
            'restrictions.*' => ['nullable', 'string', 'max:255'],
            'ingredients.*' => ['required', 'string', 'distinct', 'min:1'],
            'portion' => ['nullable', 'string'],
            'servings' => ['integer', 'min:1', 'max:20'],
            'kitchen_staples' => ['boolean'],
            'recipe_description' => ['required', 'string', 'max:4000'],
            'recipe_id' => ['required', 'integer', 'exists:recipes,id']
        ];
    }
}
