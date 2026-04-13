<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MealPlan extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'meals_per_day' => 'array',
            'dietary_restrictions' => 'array',
            'grocery_list' => 'array',
            'budget_cents' => 'integer',
            'target_calories' => 'integer',
            'target_protein' => 'integer',
            'target_carbs' => 'integer',
            'target_fat' => 'integer',
            'grocery_total_cents' => 'integer',
            'tokens_spent' => 'integer',
            'days' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $plan) {
            if (empty($plan->uuid)) {
                $plan->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(MealPlanSlot::class)->orderBy('day_number')->orderBy('sort_order');
    }

    // Helpers

    public function totalSlots(): int
    {
        return $this->days * count($this->meals_per_day ?? []);
    }

    public function slotsForDay(int $day): \Illuminate\Database\Eloquent\Collection
    {
        return $this->slots()->where('day_number', $day)->get();
    }

    public function dailyNutrition(int $day): array
    {
        $slots = $this->slotsForDay($day);
        $totals = ['calories' => 0, 'protein' => 0, 'carbs' => 0, 'fat' => 0];

        foreach ($slots as $slot) {
            $data = $slot->recipe_data;
            if ($data && isset($data['nutrition_per_serving'])) {
                $n = $data['nutrition_per_serving'];
                $totals['calories'] += $n['calories'] ?? 0;
                $totals['protein'] += $n['protein'] ?? 0;
                $totals['carbs'] += $n['carbs'] ?? 0;
                $totals['fat'] += $n['fat'] ?? 0;
            } elseif ($slot->recipe) {
                $totals['calories'] += $slot->recipe->calories_per_serving ?? 0;
                $totals['protein'] += $slot->recipe->protein_per_serving ?? 0;
                $totals['carbs'] += $slot->recipe->carbs_per_serving ?? 0;
                $totals['fat'] += $slot->recipe->fat_per_serving ?? 0;
            }
        }

        return $totals;
    }

    public function budgetFormatted(): ?string
    {
        return $this->budget_cents ? '$' . number_format($this->budget_cents / 100, 2) : null;
    }

    public function groceryTotalFormatted(): ?string
    {
        return $this->grocery_total_cents ? '$' . number_format($this->grocery_total_cents / 100, 2) : null;
    }

    public function isComplete(): bool
    {
        return $this->status === 'complete';
    }

    public function isGenerating(): bool
    {
        return $this->status === 'generating';
    }
}
