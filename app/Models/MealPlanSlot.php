<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MealPlanSlot extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'recipe_data' => 'array',
            'is_generated' => 'boolean',
            'day_number' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function mealPlan(): BelongsTo
    {
        return $this->belongsTo(MealPlan::class);
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function getNutrition(): array
    {
        if ($this->recipe_data && isset($this->recipe_data['nutrition_per_serving'])) {
            return $this->recipe_data['nutrition_per_serving'];
        }

        if ($this->recipe) {
            return [
                'calories' => $this->recipe->calories_per_serving ?? 0,
                'protein' => $this->recipe->protein_per_serving ?? 0,
                'carbs' => $this->recipe->carbs_per_serving ?? 0,
                'fat' => $this->recipe->fat_per_serving ?? 0,
            ];
        }

        return ['calories' => 0, 'protein' => 0, 'carbs' => 0, 'fat' => 0];
    }
}
