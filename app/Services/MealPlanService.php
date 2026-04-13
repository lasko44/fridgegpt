<?php

namespace App\Services;

use App\Models\MealPlan;
use App\Models\MealPlanSlot;
use App\Models\Recipe;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MealPlanService
{
    public function __construct(
        private TokenService $tokenService,
    ) {}

    /**
     * Create a meal plan with user preferences.
     */
    public function createPlan(User $user, array $options): MealPlan
    {
        return $user->mealPlans()->create([
            'name' => $options['name'] ?? $this->generateName($options),
            'days' => $options['days'] ?? 7,
            'meals_per_day' => $options['meals_per_day'] ?? ['breakfast', 'lunch', 'dinner'],
            'budget_cents' => isset($options['budget']) ? (int) ($options['budget'] * 100) : null,
            'target_calories' => $options['target_calories'] ?? null,
            'target_protein' => $options['target_protein'] ?? null,
            'target_carbs' => $options['target_carbs'] ?? null,
            'target_fat' => $options['target_fat'] ?? null,
            'dietary_restrictions' => $options['restrictions'] ?? [],
            'preferences' => $options['preferences'] ?? null,
            'status' => 'draft',
        ]);
    }

    /**
     * Generate the full meal plan using GPT.
     */
    public function generatePlan(MealPlan $plan): MealPlan
    {
        $plan->update(['status' => 'generating']);

        try {
            $user = $plan->user;
            $totalSlots = $plan->totalSlots();

            // Get user's saved recipes that match restrictions
            $savedRecipes = $this->getSavedRecipes($user, $plan->dietary_restrictions ?? []);
            $savedCount = min($savedRecipes->count(), $totalSlots);

            // Calculate how many new recipes we need
            $newRecipesNeeded = max(0, $totalSlots - $savedCount);

            // Check tokens
            if ($newRecipesNeeded > 0 && $user->token_balance < $newRecipesNeeded) {
                $plan->update(['status' => 'failed']);
                throw new Exception("Not enough tokens. Need {$newRecipesNeeded} but have {$user->token_balance}.");
            }

            // Generate the full plan via GPT
            $gptPlan = $this->callGPT($plan, $savedRecipes->take($savedCount)->toArray(), $newRecipesNeeded);

            if (!$gptPlan || empty($gptPlan['days'])) {
                $plan->update(['status' => 'failed']);
                throw new Exception('Failed to generate meal plan.');
            }

            // Build a set of slugs we actually told GPT about (original user recipes only)
            $knownSavedSlugs = $savedRecipes->pluck('slug')->filter()->all();

            // Create slots from GPT response
            $tokensSpent = 0;
            $mealTypes = $plan->meals_per_day;

            foreach ($gptPlan['days'] as $dayData) {
                $dayNum = $dayData['day'] ?? 1;

                foreach ($dayData['meals'] ?? [] as $idx => $meal) {
                    $mealType = $mealTypes[$idx] ?? 'meal';

                    // Only treat as "saved" if the slug matches one we told GPT about
                    $existingRecipeId = null;
                    $slug = $meal['recipe_slug'] ?? '';
                    if ($slug && in_array($slug, $knownSavedSlugs)) {
                        $existingRecipeId = \App\Models\Recipe::where('slug', $slug)->value('id');
                    }
                    $isActuallySaved = $existingRecipeId !== null;

                    // New recipes cost a token
                    if (! $isActuallySaved) {
                        try {
                            $this->tokenService->spend($user, 'meal_plan_recipe', [
                                'meal_plan_id' => $plan->id,
                                'day' => $dayNum,
                                'meal_type' => $mealType,
                            ]);
                            $tokensSpent++;
                        } catch (Exception $e) {
                            Log::warning('Token spend failed during meal plan', ['error' => $e->getMessage()]);
                            continue;
                        }

                        // Save the generated meal as a real Recipe
                        $recipe = $this->createRecipeFromMeal($user, $meal, $plan->dietary_restrictions ?? []);
                        $existingRecipeId = $recipe->id;
                    }

                    $plan->slots()->create([
                        'day_number' => $dayNum,
                        'meal_type' => $mealType,
                        'recipe_name' => $meal['title'] ?? 'Untitled',
                        'recipe_data' => $meal,
                        'recipe_id' => $existingRecipeId,
                        'is_generated' => ! $isActuallySaved,
                        'sort_order' => $idx,
                    ]);
                }
            }

            // Build grocery list
            $groceryList = $this->buildGroceryList($gptPlan);
            $groceryItems = $groceryList['items'] ?? [];
            $groceryTotalCents = $groceryList['total_cents'] ?? null;

            // Auto-price with Kroger BEFORE marking complete (so polling sees prices immediately)
            $user = $plan->user->fresh();
            if (! empty($groceryItems) && $user) {
                try {
                    $kroger = app(\App\Services\KrogerService::class);
                    $storeId = $user->preferred_store_id;
                    $storeName = $user->preferred_store_name ?? 'Kroger';

                    if (! $storeId && $user->zip_code) {
                        $stores = $kroger->findStores($user->zip_code, 1);
                        if (! empty($stores)) {
                            $storeId = $stores[0]['locationId'];
                            $storeName = $stores[0]['name'];
                            $user->update([
                                'preferred_store_id' => $storeId,
                                'preferred_store_name' => $storeName,
                            ]);
                        }
                    }

                    if ($storeId) {
                        $priced = $kroger->priceGroceryList($groceryItems, $storeId, ['name' => $storeName]);
                        $groceryItems = $priced['items'];
                        $groceryTotalCents = $priced['total_cents'];
                        Log::info('Auto-priced grocery list', ['plan' => $plan->uuid, 'store' => $storeName]);
                    }
                } catch (\Throwable $e) {
                    Log::warning('Auto-pricing failed, using GPT estimates', ['error' => $e->getMessage()]);
                }
            }

            $plan->update([
                'status' => 'complete',
                'tokens_spent' => $tokensSpent,
                'grocery_list' => $groceryItems,
                'grocery_total_cents' => $groceryTotalCents,
            ]);

            return $plan->fresh(['slots']);
        } catch (Exception $e) {
            if ($plan->status === 'generating') {
                $plan->update(['status' => 'failed']);
            }
            Log::error('Meal plan generation failed', ['plan_id' => $plan->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Regenerate a single slot with a new recipe.
     */
    public function regenerateSlot(MealPlanSlot $slot, User $user): MealPlanSlot
    {
        $plan = $slot->mealPlan;

        // Spend token
        $this->tokenService->spend($user, 'meal_plan_recipe', [
            'meal_plan_id' => $plan->id,
            'slot_id' => $slot->id,
            'regenerate' => true,
        ]);

        // Generate one recipe via GPT
        $response = Http::retry(2, 2000)->withHeaders([
            'Authorization' => 'Bearer ' . config('ai.openai_api_key'),
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini',
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => 'You are a chef AI. Return a single recipe as JSON with: title, description, servings, nutrition_per_serving (calories, protein, carbs, fat), ingredients (array of {name, amount, unit}), instructions (array of {step, text}).'],
                ['role' => 'user', 'content' => "Generate a {$slot->meal_type} recipe." .
                    ($plan->dietary_restrictions ? " Dietary restrictions: " . implode(', ', $plan->dietary_restrictions) . "." : '') .
                    ($plan->target_calories ? " Target ~{$plan->target_calories} calories per day, so this meal should be about " . round($plan->target_calories / count($plan->meals_per_day)) . " calories." : '')
                ],
            ],
        ]);

        $recipe = json_decode($response->json()['choices'][0]['message']['content'], true);

        $slot->update([
            'recipe_name' => $recipe['title'] ?? 'New Recipe',
            'recipe_data' => $recipe,
            'is_generated' => true,
            'recipe_id' => null,
        ]);

        // Rebuild grocery list
        $this->rebuildGroceryList($plan);

        $plan->increment('tokens_spent');

        return $slot->fresh();
    }

    /**
     * Call GPT to generate the full meal plan.
     */
    private function callGPT(MealPlan $plan, array $savedRecipes, int $newNeeded): ?array
    {
        $mealsPerDay = $plan->meals_per_day;
        $days = $plan->days;

        $savedRecipeList = '';
        if (!empty($savedRecipes)) {
            $savedRecipeList = "\n\nSAVED RECIPES (use these first — they're free, mark as is_saved:true):\n";
            foreach ($savedRecipes as $r) {
                $savedRecipeList .= "- slug:{$r['slug']} \"{$r['name']}\" (cal:{$r['calories_per_serving']}, pro:{$r['protein_per_serving']})\n";
            }
        }

        $constraints = [];
        if ($plan->target_calories) $constraints[] = "Target ~{$plan->target_calories} calories per day";
        if ($plan->target_protein) $constraints[] = "Target ~{$plan->target_protein}g protein per day";
        if ($plan->target_carbs) $constraints[] = "Target ~{$plan->target_carbs}g carbs per day";
        if ($plan->target_fat) $constraints[] = "Target ~{$plan->target_fat}g fat per day";
        if ($plan->budget_cents) {
            $budget = number_format($plan->budget_cents / 100, 2);
            $constraints[] = "STRICT grocery budget: \${$budget} total for ALL ingredients across ALL days. Choose affordable ingredients. The sum of estimated_price_cents in the grocery_list MUST NOT exceed " . $plan->budget_cents . " cents";
        }
        if ($plan->dietary_restrictions) $constraints[] = "Dietary restrictions: " . implode(', ', $plan->dietary_restrictions);
        if ($plan->preferences) $constraints[] = "Preferences: {$plan->preferences}";

        $constraintText = !empty($constraints) ? "\n\nCONSTRAINTS:\n" . implode("\n", array_map(fn($c) => "- {$c}", $constraints)) : '';

        // Location-aware pricing: use the user's zip code for area-accurate estimates
        $user = $plan->user;
        $locationHint = '';
        if ($user->zip_code) {
            $locationHint = "\n\nPRICING LOCATION: Estimate grocery prices based on average prices in the {$user->zip_code} zip code area. Use realistic local grocery store prices (Kroger, Walmart, Aldi, Publix, Safeway, etc. — whichever is typical for that region). Do NOT use generic US averages.";
        }

        $response = Http::retry(2, 3000)->timeout(240)->connectTimeout(10)->withHeaders([
            'Authorization' => 'Bearer ' . config('ai.openai_api_key'),
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini',
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "You are a meal planning AI. Create a {$days}-day meal plan.

IMPORTANT: Each day must have EXACTLY " . count($mealsPerDay) . " meal(s): " . implode(', ', $mealsPerDay) . ". Do NOT add extra meals. Do NOT add meals of types not listed. Each day's meals array must have exactly " . count($mealsPerDay) . " items.

For each meal, provide a full recipe with: title, description, servings (1), nutrition_per_serving ({calories, protein, carbs, fat}), ingredients (array of {name, amount, unit}), instructions (array of {step, text}).

Use precise measurable amounts (grams for meats, cups/tbsp for liquids). Nutrition must be accurate.

Don't repeat the same recipe twice. Ensure variety in cuisines and cooking methods.

If saved recipes are provided, use them to fill slots where they fit nutritionally. Mark saved recipes with is_saved:true and include their recipe_slug. Generate new recipes for remaining slots.{$savedRecipeList}{$constraintText}{$locationHint}

Return JSON: {days: [{day: 1, meals: [{title, description, servings, is_saved, recipe_slug, nutrition_per_serving: {calories, protein, carbs, fat}, ingredients: [{name, amount, unit}], instructions: [{step, text}]}]}], grocery_list: [{name, quantity, unit, estimated_price_cents}]}"
                ],
                [
                    'role' => 'user',
                    'content' => "Create a {$days}-day meal plan with " . implode(', ', $mealsPerDay) . " each day. I need {$newNeeded} new recipes generated."
                ],
            ],
        ]);

        $content = $response->json()['choices'][0]['message']['content'] ?? null;
        return $content ? json_decode($content, true) : null;
    }

    /**
     * Build grocery list from GPT response.
     */
    private function buildGroceryList(array $gptPlan): array
    {
        $items = $gptPlan['grocery_list'] ?? [];
        $totalCents = 0;

        foreach ($items as &$item) {
            $price = $item['estimated_price_cents'] ?? 0;
            $totalCents += $price;
            $item['price_formatted'] = '$' . number_format($price / 100, 2);
        }

        return [
            'items' => $items,
            'total_cents' => $totalCents,
        ];
    }

    /**
     * Rebuild grocery list from existing plan slots.
     */
    private function rebuildGroceryList(MealPlan $plan): void
    {
        $plan->load('slots');
        $allIngredients = [];

        foreach ($plan->slots as $slot) {
            $data = $slot->recipe_data;
            if ($data && isset($data['ingredients'])) {
                foreach ($data['ingredients'] as $ing) {
                    $key = strtolower($ing['name'] ?? '');
                    if (!isset($allIngredients[$key])) {
                        $allIngredients[$key] = $ing;
                    }
                }
            }
        }

        $plan->update(['grocery_list' => array_values($allIngredients)]);
    }

    /**
     * Get user's saved recipes matching dietary restrictions.
     */
    private function getSavedRecipes(User $user, array $restrictions): \Illuminate\Support\Collection
    {
        return $user->recipe()
            ->whereNotNull('calories_per_serving')
            // Exclude recipes auto-generated by meal plans — only count user-created originals
            ->whereDoesntHave('mealPlanSlots')
            ->latest()
            ->limit(50)
            ->select(['id', 'name', 'slug', 'calories_per_serving', 'protein_per_serving', 'carbs_per_serving', 'fat_per_serving', 'servings'])
            ->get()
            ->map(fn ($recipe) => [
                'id' => $recipe->getKey(),
                'name' => $recipe->name,
                'slug' => $recipe->slug,
                'calories_per_serving' => $recipe->calories_per_serving,
                'protein_per_serving' => $recipe->protein_per_serving,
            ]);
    }

    /**
     * Save a generated meal as a Recipe model so it has its own page/slug.
     */
    private function createRecipeFromMeal(User $user, array $meal, array $restrictions = []): \App\Models\Recipe
    {
        $title = $meal['title'] ?? 'Generated Meal';
        $description = $meal['description'] ?? '';
        $ingredients = $meal['ingredients'] ?? [];
        $instructions = $meal['instructions'] ?? [];
        $nutrition = $meal['nutrition_per_serving'] ?? [];

        // Build a description that the recipe show page can parse
        $restrictionLine = ! empty($restrictions) ? implode(', ', $restrictions) : '';
        $body = "Title: {$title}\n\n{$description}\n";
        if ($restrictionLine) {
            $body .= "Dietary: {$restrictionLine}\n";
        }
        $body .= "\n";

        if (! empty($ingredients)) {
            $body .= "Ingredients:\n";
            foreach ($ingredients as $ing) {
                $amount = $ing['amount'] ?? '';
                $unit = $ing['unit'] ?? '';
                $name = $ing['name'] ?? '';
                $body .= "- {$amount} {$unit} {$name}\n";
            }
            $body .= "\n";
        }

        if (! empty($instructions)) {
            $body .= "Instructions:\n";
            foreach ($instructions as $step) {
                $num = $step['step'] ?? '';
                $text = $step['text'] ?? '';
                $body .= "{$num}. {$text}\n";
            }
        }

        return \App\Models\Recipe::create([
            'user_id' => $user->id,
            'name' => $title,
            'slug' => \Illuminate\Support\Str::slug($title) . '-' . \Illuminate\Support\Str::random(6),
            'description' => trim($body),
            'input_ingredients' => collect($ingredients)->pluck('name')->implode(', '),
            'servings' => $meal['servings'] ?? 1,
            'calories_per_serving' => $nutrition['calories'] ?? null,
            'protein_per_serving' => $nutrition['protein'] ?? null,
            'carbs_per_serving' => $nutrition['carbs'] ?? null,
            'fat_per_serving' => $nutrition['fat'] ?? null,
            'nutrition_source' => 'gpt_estimate',
            'is_variation' => false,
        ]);
    }

    private function generateName(array $options): string
    {
        $days = $options['days'] ?? 7;
        $restrictions = $options['restrictions'] ?? [];
        $targetCalories = $options['target_calories'] ?? null;
        $preferences = $options['preferences'] ?? null;

        // Pick a descriptor from restrictions
        $descriptor = null;
        $priority = ['Keto', 'Paleo', 'Vegan', 'Vegetarian', 'Mediterranean', 'Whole30', 'Pescatarian', 'High-Protein', 'Low-Carb', 'Gluten-Free', 'Dairy-Free'];
        foreach ($priority as $p) {
            if (in_array($p, $restrictions, true)) {
                $descriptor = $p;
                break;
            }
        }

        // Build the name
        if ($descriptor) {
            return "{$days}-Day {$descriptor} Plan";
        }

        if ($targetCalories) {
            if ($targetCalories <= 1500) {
                return "{$days}-Day Lean Plan";
            }
            if ($targetCalories >= 2500) {
                return "{$days}-Day Bulk Plan";
            }
        }

        // Date-based fallback
        $date = now()->format('M j');

        return "{$days}-Day Plan ({$date})";
    }
}
