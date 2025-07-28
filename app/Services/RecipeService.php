<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RecipeService
{
    private string $recipe;
    private array $userIngredients;
    private array $parsedRecipe;
    private string $guestCacheKey;
    private ?User $user = null;

    private const INGREDIENTS = 'Ingredients';
    private const INSTRUCTIONS = 'Instructions';
    private const GUEST_LIMIT_ERROR = 'Daily limit reached. Please try again tomorrow.';
    private const SUBSCRIPTION_LIMIT_ERROR = 'You have reached your daily recipe limit. 
    Please subscribe to get more recipes.';
    private const GUEST_CACHE = 'guest_recipes_';
    /**
     * Get the generated recipe.
     *
     * @param array $ingredients
     * @return RecipeService
     * @throws ConnectionException
     */
    public function generateRecipe(array $ingredients): self
    {
        $this->userIngredients = $ingredients;
        $ingredientList = implode(',', $ingredients);

        $response = Http::retry(3, 2000)->withHeaders([
            'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'user', 'content' => "I have these ingredients: $ingredientList. Give me a recipe. Spit the recipe into title, ingredients, and instructions. Use the following format:\n\nTitle: [Recipe Title]\n\nIngredients:\n [Ingredient 1]\n [Ingredient 2]\n\nInstructions:\n1. [Step 1]\n2. [Step 2]\n3. [Step 3]"],
            ],
        ]);

        $this->recipe = $response->json()['choices'][0]['message']['content'];
        $this->parsedRecipe = $this->parse();
        return $this;
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

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
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
     * Get the latest recipes for a user or guest.
     *
     * @param User|null $user
     * @param string|null $ip
     * @return array
     */
    public function getRecipes(User $user = null, string $ip = null): array
    {
        $cacheKey = $ip ? 'guest_recipes_'.$ip : null;
        $recipes = null;

        //Guest user recipes
        if(!$user && $cacheKey) {
            $recipes = Cache::get($cacheKey, null);
        }
        //Standard user recipes
        if($user && !$user->is_subscribed) {
            $recipes = $user->recipe()->latest()->take(5)->get() ?? null;
        }
        //Premium user recipes
        if($user && $user->is_subscribed) {
            $recipes = $user->recipe()->latest()->paginate(5) ?? null;
        }

        return $recipes ?: [];
    }

    /**
     * @throws ConnectionException
     * @throws Exception
     */
    public function guestStore(array $ingredients, string $ip): RecipeService
    {
        $cacheKey = 'guest_recipes_' . $ip;
        $this->guestCacheKey = $cacheKey;
        $recipes = Cache::get($cacheKey, []);

        if (count($recipes) >= 3) {
            throw new Exception(self::GUEST_LIMIT_ERROR);
        }

        $recipe = $this->generateRecipe($ingredients);
        $recipes[Str::uuid()->toString()] = $recipe->toArray();
        Cache::put($cacheKey, $recipes, now()->addDay());

        return $recipe;
    }

    /**
     * @throws ConnectionException
     * @throws Exception
     */
    public function standardStore (array $ingredients, User $user): RecipeService
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
    public function storePremium(array $ingredients, User $user): RecipeService
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
     */
    public function getStandardRecipes(User $user = null): array
    {
        $standardUser = $user ?? $this->user;
        if(!$standardUser) {
        }
        return $standardUser->recipe()->latest()->take(5)->get()->toArray();
    }

    /**
     * Get the latest premium recipes for a premium user.
     *
     * @param User|null $user
     * @return LengthAwarePaginator|null
     */
    public function getPremiumRecipes(User $user = null): ?LengthAwarePaginator
    {
        $premiumUser = $user ?? $this->user;
        return $premiumUser->recipe()->latest()->paginate(5) ?? null;
    }

    /**
     * @throws Exception
     */
    private function storeUserRecipe(User $user, RecipeService $recipe): void
    {

        $userRecipe = $user->recipe()->create([
            'name' => $recipe->title(),
            'description' => $recipe->get(),
        ]);

        try {
            $userRecipe->ingredients()->createMany($recipe->ingredients());
        }
        catch (Exception $e) {
            $userRecipe->delete();
            throw new Exception('Failed to store recipe ingredients: ' . $e->getMessage());
        }
    }
}