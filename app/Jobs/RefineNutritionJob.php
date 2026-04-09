<?php

namespace App\Jobs;

use App\Models\Recipe;
use App\Services\NutritionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefineNutritionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;
    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(private int $recipeId) {}

    public function handle(NutritionService $nutritionService): void
    {
        $recipe = Recipe::find($this->recipeId);
        if (!$recipe) {
            return;
        }

        try {
            $nutritionService->refineRecipeNutrition($recipe);
        } catch (\Exception $e) {
            Log::error('Nutrition refinement failed', [
                'recipe_id' => $this->recipeId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
