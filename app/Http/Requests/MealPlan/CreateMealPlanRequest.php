<?php

namespace App\Http\Requests\MealPlan;

use Illuminate\Foundation\Http\FormRequest;

class CreateMealPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:100'],
            'days' => ['required', 'integer', 'min:1', 'max:14'],
            'meals_per_day' => ['required', 'array', 'min:1'],
            'meals_per_day.*' => ['string', 'in:breakfast,lunch,dinner,snack'],
            'budget' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'target_calories' => ['nullable', 'integer', 'min:500', 'max:10000'],
            'target_protein' => ['nullable', 'integer', 'min:0', 'max:500'],
            'target_carbs' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'target_fat' => ['nullable', 'integer', 'min:0', 'max:500'],
            'restrictions' => ['nullable', 'array'],
            'restrictions.*' => ['string', 'max:50'],
            'preferences' => ['nullable', 'string', 'max:500'],
            'zip_code' => ['nullable', 'string', 'regex:/^\d{5}$/'],
            'store_id' => ['nullable', 'string', 'max:50'],
            'store_name' => ['nullable', 'string', 'max:100'],
        ];
    }
}
