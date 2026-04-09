<?php

namespace App\Services;

use App\Exceptions\UserNotFoundException;
use App\Facades\Guest;
use App\Facades\ModelSlugger;
use App\Models\Recipe;
use App\Models\User;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RecipeService
{
    private string $recipe;
    private array $userIngredients;
    private array $parsedRecipe;
    private string $guestCacheKey;
    private ?User $user = null;
    private EmbeddingService $embeddingService;

    private const INGREDIENTS = 'Ingredients';
    private const INSTRUCTIONS = 'Instructions';
    private const GUEST_LIMIT_ERROR = 'Daily limit reached. Please try again tomorrow.';
    private const SUBSCRIPTION_LIMIT_ERROR = 'You have reached your daily recipe limit.
    Please subscribe to get more recipes.';
    private const GUEST_CACHE = 'guest_recipes_';

    public function __construct()
    {
        $this->embeddingService = new EmbeddingService();
    }

    /**
     * Get the generated recipe using RAG — retrieves similar past recipes
     * for context to generate better, more creative results.
     *
     * @param array $ingredients
     * @return RecipeService
     * @throws ConnectionException
     */
    public function generateRecipe(array $ingredients, array $restrictions = [], array $options = []): self
    {
        $this->userIngredients = $ingredients;
        $ingredientList = implode(', ', $ingredients);

        // RAG: embed the ingredients and find similar past recipes
        $ragContext = $this->retrieveSimilarRecipes($ingredientList);

        $systemPrompt = "You are a creative chef AI. You generate delicious, well-structured recipes from user-provided ingredients. Always respond with valid JSON only, no markdown.";

        if (!empty($restrictions)) {
            $restrictionList = implode(', ', $restrictions);
            $systemPrompt .= "\n\nIMPORTANT: The user has the following dietary restrictions that MUST be respected: {$restrictionList}. Do NOT include any ingredients that violate these restrictions. Suggest appropriate substitutions where needed.";
        }

        // Servings
        $servings = $options['servings'] ?? null;
        if ($servings) {
            $systemPrompt .= "\n\nMake this recipe for exactly {$servings} servings.";
        }

        // Portion size
        $portion = $options['portion'] ?? null;
        if ($portion && $portion !== 'Single') {
            $systemPrompt .= "\n\nMake {$portion}-sized portions (larger than standard).";
        }

        // Kitchen staples
        $includeStaples = $options['include_staples'] ?? true;
        if (!$includeStaples) {
            $systemPrompt .= "\n\nDo NOT include common pantry staples (salt, pepper, oil, etc.). Only use the ingredients the user provided.";
        }

        // Extra ingredients
        $allowExtras = $options['allow_extras'] ?? true;
        if (!$allowExtras) {
            $systemPrompt .= "\n\nOnly use the exact ingredients the user listed. Do NOT add any additional ingredients.";
        }

        if ($ragContext) {
            $systemPrompt .= "\n\nHere are some similar recipes that have been made before. Use them as inspiration to create something new and different — do NOT copy them directly, but learn from the ingredient combinations and techniques:\n\n" . $ragContext;
        }

        $response = Http::retry(3, 2000)->withHeaders([
            'Authorization' => 'Bearer ' . config('ai.openai_api_key'),
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini',
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemPrompt,
                ],
                [
                    'role' => 'user',
                    'content' => "I have these ingredients: $ingredientList." . (!empty($restrictions) ? " My dietary restrictions: " . implode(', ', $restrictions) . "." : "") . " Create a recipe and return it as JSON with this exact structure:
{
  \"title\": \"Recipe Name\",
  \"description\": \"A brief 1-2 sentence description of the dish\",
  \"prep_time\": \"10 mins\",
  \"cook_time\": \"20 mins\",
  \"servings\": 2,
  \"nutrition_per_serving\": {\"calories\": 450, \"protein\": 35, \"carbs\": 40, \"fat\": 12},
  \"ingredients\": [
    {\"name\": \"Chicken breast, boneless skinless\", \"amount\": \"350\", \"unit\": \"g\", \"nutrition_estimate\": {\"calories\": 577, \"protein\": 71, \"carbs\": 0, \"fat\": 8}},
    {\"name\": \"Garlic, minced\", \"amount\": \"2\", \"unit\": \"cloves\", \"nutrition_estimate\": {\"calories\": 9, \"protein\": 0, \"carbs\": 2, \"fat\": 0}},
    {\"name\": \"Olive oil\", \"amount\": \"2\", \"unit\": \"tbsp\", \"nutrition_estimate\": {\"calories\": 240, \"protein\": 0, \"carbs\": 0, \"fat\": 28}}
  ],
  \"instructions\": [
    {\"step\": 1, \"text\": \"Season the chicken with salt and pepper.\", \"tip\": \"Pat dry first for better browning\"},
    {\"step\": 2, \"text\": \"Heat oil in a skillet over medium-high heat.\"}
  ]
}
IMPORTANT rules for ingredients:
- Use precise measurable units: grams (g), ml, cups, tbsp, tsp, cloves, or specific counts (e.g. \"2 large eggs\" not \"2 pieces eggs\")
- For meats, use grams (e.g. \"350 g\" not \"2 pieces\")
- For liquids, use ml or tbsp/tsp
- For produce, use grams or descriptive sizes (e.g. \"1 medium onion, diced\" not \"1 pieces onion\")
- Include preparation notes in the name (e.g. \"Chicken breast, boneless skinless, diced\")
- The \"nutrition_estimate\" should be the TOTAL nutrition for that exact amount, not per 100g
- The \"nutrition_per_serving\" should be accurate: sum all ingredient nutrition then divide by servings
- The \"tip\" field in instructions is optional
- Include all the user's ingredients plus any common pantry staples needed"
                ],
            ],
        ]);

        $content = $response->json()['choices'][0]['message']['content'];
        $parsed = json_decode($content, true);

        if ($parsed && isset($parsed['title'])) {
            $this->parsedRecipe = $parsed;
            // Build a readable text version for storage
            $this->recipe = $this->buildTextFromStructured($parsed);
        } else {
            // Fallback to raw text parsing if JSON fails
            $this->recipe = $content;
            $this->parsedRecipe = $this->parse();
        }

        return $this;
    }

    /**
     * Use vector similarity search to find past recipes with similar ingredients.
     */
    private function retrieveSimilarRecipes(string $ingredientList): ?string
    {
        try {
            $embedding = $this->embeddingService->embed("Recipe ingredients: " . $ingredientList);
            $similar = Recipe::findSimilar($embedding, 3, 0.35);

            if (empty($similar)) {
                return null;
            }

            $context = '';
            foreach ($similar as $i => $recipe) {
                $num = $i + 1;
                $context .= "--- Recipe {$num} (similarity: " . round($recipe->similarity, 2) . ") ---\n";
                $context .= "Title: {$recipe->name}\n";
                $context .= "Ingredients used: {$recipe->input_ingredients}\n";
                $context .= "{$recipe->description}\n\n";
            }

            return $context;
        } catch (Exception $e) {
            Log::warning('RAG retrieval failed, proceeding without context', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get the generated recipe.
     *
     * @return string
     */
    public function get(): string
    {

        return $this->recipe;
    }

    /**
     * Convert the recipe to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->parsedRecipe;
    }

    /**
     * Get the structured recipe data (JSON parsed).
     */
    public function structured(): array
    {
        return $this->parsedRecipe;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Build readable text from structured JSON for database storage.
     */
    private function buildTextFromStructured(array $data): string
    {
        $text = "Title: " . ($data['title'] ?? 'Untitled') . "\n\n";

        if (!empty($data['description'])) {
            $text .= $data['description'] . "\n\n";
        }

        $text .= "Ingredients:\n";
        foreach ($data['ingredients'] ?? [] as $ing) {
            $amount = trim(($ing['amount'] ?? '') . ' ' . ($ing['unit'] ?? ''));
            $text .= "- " . ($amount ? "$amount " : '') . ($ing['name'] ?? '') . "\n";
        }

        $text .= "\nInstructions:\n";
        foreach ($data['instructions'] ?? [] as $step) {
            $text .= ($step['step'] ?? '') . ". " . ($step['text'] ?? '') . "\n";
        }

        return trim($text);
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return Arr::get($this->parsedRecipe, 'title');
    }

    /**
     * @return array
     */
    public function ingredients(): array
    {
        return Arr::get($this->parsedRecipe, 'ingredients', []);
    }

    /**
     * @return array
     */
    public function userIngredients(): array
    {
        return $this->userIngredients;
    }

    /**
     * @return array
     */
    private function parse(): array
    {
        $recipeText = $this->recipe;

        // Extract title
        preg_match('/^Title:\s*(.+)$/m', $recipeText, $titleMatch);
        $title = isset($titleMatch[1]) ? trim($titleMatch[1]) : '';

        // Extract ingredients
        preg_match('/Ingredients:\s*\n((?:- .*\n?)*)/m', $recipeText, $ingredientsMatch);
        $ingredientsRaw = $ingredientsMatch[1] ?? '';
        $ingredients = array_map(function ($line) {
            return ['name' => trim(ltrim($line, "- "))];
        }, array_filter(explode("\n", $ingredientsRaw)));

        // Extract instructions
        preg_match('/Instructions:\s*\n((?:\d+\..*\n?)*)/m', $recipeText, $instructionsMatch);
        $instructionsRaw = $instructionsMatch[1] ?? '';
        $instructions = array_map(function ($line) {
            return trim(preg_replace('/^\d+\.\s*/', '', $line));
        }, array_filter(explode("\n", $instructionsRaw)));

        return [
            'title' => $title,
            'ingredients' => $ingredients,
            'instructions' => $instructions,
        ];
    }

    /**
     * Create a recipe for a user or guest using the token system.
     *
     * @param array<int, string> $ingredients
     * @param User|null $user
     * @param string|null $ip
     * @return array<string, mixed>
     * @throws Exception
     */
    public function createRecipeForUser(array $ingredients, ?User $user, ?string $ip, array $restrictions = [], array $options = []): array
    {
        // Guest: 1 free recipe per day, no tokens needed
        if (!$user) {
            $recipe = $this->generateRecipe($ingredients, $restrictions, $options);
            $this->guestStore($ingredients, $ip, $recipe);
            return [
                'recipe' => $recipe->get(),
                'structured' => $recipe->structured(),
                'flash' => ['success' => 'Recipe created successfully!']
            ];
        }

        // Authenticated: spend 1 token
        $tokenService = new TokenService();

        if (!$tokenService->hasTokensFor($user, 'recipe')) {
            throw new Exception('You\'re out of tokens! Buy more to keep generating recipes.');
        }

        $recipe = $this->generateRecipe($ingredients, $restrictions, $options);
        $this->storeUserRecipe($user, $recipe);

        // Deduct token after successful generation
        $tokenService->spend($user, 'recipe', [
            'ingredients' => $ingredients,
        ]);

        return [
            'recipe' => $recipe->get(),
            'structured' => $recipe->structured(),
            'flash' => ['success' => 'Recipe created successfully!']
        ];
    }

    /**
     * Get the latest recipes for a user or guest.
     *
     * @param User|null $user
     * @param string|null $ip
     * @return array
     */
    public function getRecipes(?User $user = null, ?string $ip = null): array
    {
        $cacheKey = $ip ? 'guest_recipes_' . $ip : null;
        $recipes = null;

        //Guest user recipes
        if (!$user && $cacheKey) {
            $recipes = Cache::get($cacheKey, null);
        }
        //Standard user recipes
        if ($user && !$user->is_subscribed) {
            $recipes = $user->recipe()->latest()->take(5)->get() ?? null;
        }
        //Premium user recipes
        if ($user && $user->is_subscribed) {
            $recipes = $user->recipe()->latest()->paginate(5) ?? null;
        }

        return $recipes ?: [];
    }

    /**
     * @throws ConnectionException
     * @throws Exception
     */
    public function guestStore(array $ingredients, string $ip, ?RecipeService $existingRecipe = null): RecipeService
    {
        $cacheKey = 'guest_recipes_' . $ip;
        $this->guestCacheKey = $cacheKey;
        $recipes = Cache::get($cacheKey, []);

        $guestLimit = (int) config('tokens.guest_free_recipes', 1);
        if (count($recipes) >= $guestLimit) {
            throw new Exception('Sign up for a free account to keep generating recipes!');
        }

        $recipe = $existingRecipe ?? $this->generateRecipe($ingredients);
        $recipes[Str::uuid()->toString()] = $recipe->toArray();
        Cache::put($cacheKey, $recipes, now()->addDay());

        return $recipe;
    }

    /**
     * @throws ConnectionException
     * @throws Exception
     */
    public function standardStore(array $ingredients, User $user): RecipeService
    {
        if ($user->dayRecipeCount() > 2) {
            throw new Exception(self::SUBSCRIPTION_LIMIT_ERROR);
        }

        $recipe = $this->generateRecipe($ingredients);

        if ($user->recipeCount() > 4) {
            $user->deleteOldestRecipe();
        }
        $this->storeUserRecipe($user, $recipe, $ingredients);

        return $recipe;
    }

    /**
     * @throws ConnectionException
     * @throws Exception
     * Store a premium recipe for a user.
     */
    public function premiumStore(array $ingredients, User $user): RecipeService
    {
        $recipe = $this->generateRecipe($ingredients);

        // Create the recipe in the database
        $this->storeUserRecipe($user, $recipe, $ingredients);

        return $recipe;
    }

    /**
     * Get the latest guest recipes.
     *
     * @param string|null $ip
     * @return array
     */
    public function getGuestRecipes(string $ip = null): array
    {
        $cacheKey = $this->guestCacheKey;
        if ($ip) {
            $cacheKey = 'guest_recipes_' . $ip;
        }
        return Cache::get($cacheKey, []);
    }

    /**
     * Get the latest standard recipes for a standard user.
     *
     * @param User|null $user
     * @return array
     * @throws UserNotFoundException
     */
    public function getStandardRecipes(User $user = null): array
    {
        $standardUser = $user ?? $this->user;

        if (!$standardUser) {
            throw new UserNotFoundException();
        }

        return $standardUser->recipe()->latest()->take(5)->get()->toArray();
    }

    /**
     * Get the latest premium recipes for a premium user.
     *
     * @param User|null $user
     * @return LengthAwarePaginator|null
     * @throws UserNotFoundException
     */
    public function getPremiumRecipes(User $user = null): ?LengthAwarePaginator
    {
        $premiumUser = $user ?? $this->user;

        if (!$premiumUser) {
            throw new UserNotFoundException();
        }

        return $premiumUser->recipe()->latest()->paginate(5) ?? null;
    }

    /**
     * @throws Exception
     */
    private function storeUserRecipe(User $user, RecipeService $recipe): void
    {
        $ingredientList = implode(', ', $recipe->userIngredients());
        $structured = $recipe->structured();

        // Extract nutrition from structured GPT response
        $nutritionPerServing = $structured['nutrition_per_serving'] ?? null;
        $servings = $structured['servings'] ?? null;

        $userRecipe = $user->recipe()->create([
            'name' => $recipe->title(),
            'slug' => ModelSlugger::slug(Recipe::class, $recipe->title()),
            'image_url' => null,
            'description' => $recipe->get(),
            'input_ingredients' => $ingredientList,
            'servings' => $servings,
            'calories_per_serving' => $nutritionPerServing['calories'] ?? null,
            'protein_per_serving' => $nutritionPerServing['protein'] ?? null,
            'carbs_per_serving' => $nutritionPerServing['carbs'] ?? null,
            'fat_per_serving' => $nutritionPerServing['fat'] ?? null,
            'nutrition_source' => $nutritionPerServing ? 'gpt_estimate' : null,
        ]);

        // Save ingredients with nutrition estimates
        try {
            $ingredientData = $structured['ingredients'] ?? $recipe->ingredients();
            $mappedIngredients = array_map(function ($ing) {
                $nutrition = $ing['nutrition_estimate'] ?? null;
                return [
                    'name' => $ing['name'] ?? '',
                    'amount' => $ing['amount'] ?? null,
                    'unit' => $ing['unit'] ?? null,
                    'calories' => $nutrition['calories'] ?? null,
                    'protein' => $nutrition['protein'] ?? null,
                    'carbs' => $nutrition['carbs'] ?? null,
                    'fat' => $nutrition['fat'] ?? null,
                    'nutrition_source' => $nutrition ? 'gpt_estimate' : null,
                ];
            }, $ingredientData);

            $userRecipe->ingredients()->createMany($mappedIngredients);
        } catch (Exception $e) {
            $userRecipe->delete();
            throw new Exception('Failed to store recipe ingredients: ' . $e->getMessage());
        }

        // Generate and store embedding for RAG retrieval
        try {
            $embeddingText = "Recipe: {$recipe->title()}. Ingredients: {$ingredientList}. {$recipe->get()}";
            $embedding = $this->embeddingService->embed($embeddingText);
            $userRecipe->setEmbedding($embedding);
        } catch (Exception $e) {
            Log::warning('Failed to store recipe embedding', ['recipe_id' => $userRecipe->id, 'error' => $e->getMessage()]);
        }

        // Dispatch background job to refine nutrition via USDA
        try {
            \App\Jobs\RefineNutritionJob::dispatch($userRecipe->id);
        } catch (Exception $e) {
            Log::warning('Failed to dispatch nutrition refinement', ['recipe_id' => $userRecipe->id]);
        }
    }
}
