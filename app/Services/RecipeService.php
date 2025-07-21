<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class RecipeService
{
    private string $recipe;
    private array $userIngredients;
    private array $parsedRecipe;

    private const INGREDIENTS = 'Ingredients';
    private const INSTRUCTIONS = 'Instructions';
    private const TEST = "Greek Chicken Sandwich:\n\nIngredients:\n- 2 slices of bread\n- Handful of spinach\n- Handful of olives, sliced\n- Sliced cooked chicken breast\n- Feta cheese (optional)\n- Hummus (optional)\n\nInstructions:\n1. Toast the bread slices in a toaster or on a skillet until golden brown.\n2. Spread a thin layer of hummus on one side of each bread slice.\n3. Layer the cooked chicken breast, spinach, olives, and feta cheese on one bread slice.\n4. Place the second bread slice on top to create a sandwich.\n5. Cut the sandwich in half and serve. Enjoy!";

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
                ['role' => 'user', 'content' => "I have these ingredients: $ingredientList. Give me a recipe."]
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
        $recipeText = self::TEST;
        // Extract title
        preg_match('/^(.*?):/', $recipeText, $titleMatch);
        $title = isset($titleMatch[1]) ? trim($titleMatch[1]) : '';

        // Extract ingredients
        preg_match('/Ingredients:\n(.*?)\n\nInstructions:/s', $recipeText, $ingredientsMatch);
        $ingredientsRaw = $ingredientsMatch[1] ?? '';
        $ingredients = array_map(function ($line) {
            return trim(ltrim($line, "- "));
        }, array_filter(explode("\n", $ingredientsRaw)));

        // Extract instructions
        preg_match('/Instructions:\n(.*)$/s', $recipeText, $instructionsMatch);
        $instructionsRaw = $instructionsMatch[1] ?? '';
        $instructions = array_map(function ($line) {
            // Remove numbering and trim
            return trim(preg_replace('/^\d+\.\s*/', '', $line));
        }, array_filter(explode("\n", $instructionsRaw)));

        return [
            'title' => $title,
            'ingredients' => $ingredients,
            'instructions' => $instructions,
        ];
    }
}