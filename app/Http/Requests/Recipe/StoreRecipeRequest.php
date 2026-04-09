<?php

namespace App\Http\Requests\Recipe;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for validating recipe creation data.
 */
class StoreRecipeRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ingredients' => ['required', 'array', 'min:1', 'max:20'],
            'ingredients.*' => ['required', 'string', 'max:100'],
            'restrictions' => ['nullable', 'array', 'max:10'],
            'restrictions.*' => ['string', 'max:50'],
            'servings' => ['nullable', 'integer', 'min:1', 'max:20'],
            'portion' => ['nullable', 'string', 'in:Single,Double,Triple'],
            'include_staples' => ['nullable', 'boolean'],
            'allow_extras' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ingredients.required' => 'Please provide at least one ingredient.',
            'ingredients.array' => 'Ingredients must be provided as a list.',
            'ingredients.min' => 'Please provide at least 1 ingredient.',
            'ingredients.max' => 'You can provide a maximum of 20 ingredients.',
            'ingredients.*.required' => 'Each ingredient must have a value.',
            'ingredients.*.max' => 'Each ingredient must be less than 100 characters.',
        ];
    }

    /**
     * Get the validated ingredients array.
     *
     * @return array<int, string>
     */
    public function getIngredients(): array
    {
        return $this->validated('ingredients');
    }

    public function getRestrictions(): array
    {
        return $this->input('restrictions', []);
    }
}
