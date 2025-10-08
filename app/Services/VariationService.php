<?php

namespace App\Services;

use App\Facades\ModelSlugger;
use App\Models\Recipe;
use App\Models\User;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Http;
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

    /**
     * @param array $data
     * @return VariationService
     * @throws ConnectionException
     */
    public function generate(array $data): VariationService
    {
        //set the class properties
        $this->setAllProperties($data);
        $this->call($data);

        return $this;
    }

    /**
     * @throws Exception
     */
    public function store(User $user): Recipe
    {
        $variation = $user->recipe()->create([
            'name' => $this->title ?? 'Untitled Variation',
            'slug' => ModelSlugger::slug(Recipe::class, $this->title ?? 'Untitled Variation'),
            'description' => $this->getRecipeVariation(),
            'is_variation' => true,
            'recipe_id' => $this->getRecipeId(),
        ]);


        try {
            $variation->ingredients()->createMany($this->ingredients);
            $variation->recipeRestriction()->createMany($this->mapRestrictons());

            return $variation;
        } catch (Exception $e) {

            $variation->forceDelete();
            throw new Exception('Failed to save Variation: ' . $e->getMessage());
        }

    }

    // region Getters and Setters
    public function getIngredients(): array
    {
        return $this->ingredients;
    }

    public function setIngredients(array $ingredients): void
    {
        $this->ingredients = $ingredients;
    }

    public function getRestrictions(): array
    {
        return $this->restrictions;
    }

    public function setRestrictions(array $restrictions): void
    {
        $this->restrictions = $restrictions;
    }

    public function getPortion(): string
    {
        return $this->portion;
    }

    public function setPortion(string $portion): void
    {
        $this->portion = $portion;
    }

    public function getServings(): int
    {
        return $this->servings;
    }

    public function setServings(int $servings): void
    {
        $this->servings = $servings;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getRecipeVariation()
    {
        return $this->recipeVariation;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getRecipeId(): int
    {
        return $this->recipeId;
    }

    public function setRecipeId(int $recipeId): void
    {
        $this->recipeId = $recipeId;
    }


    public function setAllProperties(array $data): void
    {
        $this->setIngredients(Arr::get($data, 'ingredients', []));
        $this->setRestrictions(Arr::get($data, 'restrictions', []));
        $this->setPortion(Arr::get($data, 'portion', ''));
        $this->setServings(Arr::get($data, 'servings', 1));
        $this->setDescription(Arr::get($data, 'recipe_description', ''));
        $this->setRecipeId(Arr::get($data, 'recipe_id', 0));
    }
    // endregion

    /**
     * @param array $data
     * @return void
     * @throws ConnectionException
     */
    private function call(array $data): void
    {
        $response = Http::retry(3, 2000)->withHeaders([
            'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $this->createMessage($data),
                ],
            ],
        ]);

        $this->recipeVariation = $response->json()['choices'][0]['message']['content'];
        $this->title = $this->extractTitle($this->recipeVariation);
    }

    /**
     * @param array $data
     * @return string
     */
    private function createMessage(array $data): string
    {
        $portion = $this->portion;
        $servings = $this->servings;
        $description = $this->description;

        $ingredients = Arr::join($this->ingredients, "\n- ", '', '');
        $restrictions = Arr::join($this->restrictions, "\n- ", '', '');

        return "Create a variation of the following recipe.
        Portion: {$portion}
        Servings: {$servings}

        Recipe Description:
        {$description}

        Ingredients:
        - {$ingredients}

        Dietary Restrictions to consider:
        - {$restrictions}

        Please provide a new recipe variation that fits these restrictions and uses the listed ingredients.
        Format the title the recipe like Title: <Recipe Title>.
        provide a list of ingredients like this Ingredients:
        followed by step-by-step cooking instructions.
        List the restrictions like Restrictions: Then list portion sizes number of servings and if kitchen
        staples are included.";

    }

    /**
     * @throws Exception
     */
    private function extractTitle(string $input): ?string
    {
        if (preg_match('/Title:\s*(.+)/', $input, $matches)) {
            $title = trim($matches[1]);
            $this->title = $title;
            return $title;
        }
        throw new Exception('Title not not Extracted from AI response');
    }

    private function mapRestrictons(): array
    {
        return array_map(function ($restriction) {
            return ['name' => $restriction];
        }, $this->restrictions ?? []);

    }


}
