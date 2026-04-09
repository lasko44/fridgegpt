<?php

namespace App\Services;

use App\Facades\ModelSlugger;
use App\Models\Recipe;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;

class VariationService
{
    private ?array $ingredients = null;
    private ?array $restrictions = null;
    private ?string $portion = null;
    private ?int $servings = null;
    private ?string $description = null;
    private ?int $recipeId = null;
    private ?string $recipeVariation = null;
    private ?string $title = null;
    private EmbeddingService $embeddingService;

    public function __construct()
    {
        $this->embeddingService = new EmbeddingService();
    }

    /**
     * @throws ConnectionException
     */
    public function generate(array $data): VariationService
    {
        $this->setAllProperties($data);
        $this->call($data);
        return $this;
    }

    /**
     * @throws Exception
     */
    public function store(Authenticatable $user): Recipe
    {
        $ingredientList = implode(', ', $this->ingredients ?? []);

        $variation = $user->recipe()->create([
            'name' => $this->title ?? 'Untitled Variation',
            'slug' => ModelSlugger::slug(Recipe::class, $this->title ?? 'Untitled Variation'),
            'description' => $this->getRecipeVariation(),
            'is_variation' => true,
            'recipe_id' => $this->getRecipeId(),
            'input_ingredients' => $ingredientList,
        ]);

        try {
            $variation->ingredients()->createMany($this->mapIngredients());
            $variation->recipeRestriction()->createMany($this->mapRestrictions());

            // Store embedding for RAG
            try {
                $embeddingText = "Recipe: {$this->title}. Ingredients: {$ingredientList}. {$this->getRecipeVariation()}";
                $embedding = $this->embeddingService->embed($embeddingText);
                $variation->setEmbedding($embedding);
            } catch (Exception $e) {
                Log::warning('Failed to store variation embedding', ['error' => $e->getMessage()]);
            }

            return $variation;
        } catch (Exception $e) {
            $variation->forceDelete();
            throw new Exception('Failed to save Variation: ' . $e->getMessage());
        }
    }

    public function getRecipeVariation(): ?string
    {
        return $this->recipeVariation;
    }

    public function getRecipeId(): int
    {
        return $this->recipeId;
    }

    public function setAllProperties(array $data): void
    {
        $this->ingredients = Arr::get($data, 'ingredients', []);
        $this->restrictions = Arr::get($data, 'restrictions', []);
        $this->portion = Arr::get($data, 'portion', 'Single');
        $this->servings = Arr::get($data, 'servings', 1);
        $this->description = Arr::get($data, 'recipe_description', '');
        $this->recipeId = Arr::get($data, 'recipe_id', 0);
    }

    /**
     * @throws ConnectionException
     */
    private function call(array $data): void
    {
        // RAG: find similar recipes for context
        $ragContext = $this->retrieveSimilarRecipes();

        $systemPrompt = "You are a creative chef AI that creates recipe variations. You modify existing recipes based on dietary restrictions, portion sizes, and ingredient changes.";

        if ($ragContext) {
            $systemPrompt .= "\n\nHere are some similar recipes for inspiration:\n\n" . $ragContext;
        }

        $response = Http::retry(3, 2000)->withHeaders([
            'Authorization' => 'Bearer ' . config('ai.openai_api_key'),
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemPrompt,
                ],
                [
                    'role' => 'user',
                    'content' => $this->createMessage(),
                ],
            ],
        ]);

        $this->recipeVariation = $response->json()['choices'][0]['message']['content'];
        $this->title = $this->extractTitle($this->recipeVariation);
    }

    private function retrieveSimilarRecipes(): ?string
    {
        try {
            $ingredientList = implode(', ', $this->ingredients ?? []);
            if (empty($ingredientList)) return null;

            $embedding = $this->embeddingService->embed("Recipe ingredients: " . $ingredientList);
            $similar = Recipe::findSimilar($embedding, 3, 0.35);

            if (empty($similar)) return null;

            $context = '';
            foreach ($similar as $i => $recipe) {
                $num = $i + 1;
                $context .= "--- Recipe {$num} ---\n";
                $context .= "Title: {$recipe->name}\n";
                $context .= "{$recipe->description}\n\n";
            }
            return $context;
        } catch (Exception $e) {
            Log::warning('RAG retrieval failed for variation', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function createMessage(): string
    {
        $ingredients = implode(', ', $this->ingredients ?? []);
        $restrictions = implode(', ', $this->restrictions ?? []);

        return "Create a variation of the following recipe.

Portion size: {$this->portion}
Number of servings: {$this->servings}

Original Recipe:
{$this->description}

Available Ingredients: {$ingredients}
" . ($restrictions ? "Dietary Restrictions: {$restrictions}" : '') . "

Provide the variation using this format:
Title: [New Recipe Title]

Ingredients:
- [ingredient 1]
- [ingredient 2]

Instructions:
1. [Step 1]
2. [Step 2]";
    }

    /**
     * @throws Exception
     */
    private function extractTitle(string $input): ?string
    {
        if (preg_match('/Title:\s*(.+)/', $input, $matches)) {
            return trim($matches[1]);
        }
        throw new Exception('Title not extracted from AI response');
    }

    private function mapRestrictions(): array
    {
        return array_map(fn($r) => ['name' => $r], $this->restrictions ?? []);
    }

    private function mapIngredients(): array
    {
        return array_map(fn($i) => ['name' => $i], $this->ingredients ?? []);
    }
}
