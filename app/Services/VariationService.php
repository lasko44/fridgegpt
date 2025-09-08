<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;

class VariationService
{
    /**
     * @param array $data
     * @return string
     * @throws \Illuminate\Http\Client\ConnectionException
     */
    public function generate(array $data): string
    {
        return $this->call($data);
    }

    /**
     * @param array $data
     * @return string
     * @throws \Illuminate\Http\Client\ConnectionException
     */
    private function call(array $data): string
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
        return $response->json()['choices'][0]['message']['content'];;
    }

    /**
     * @param array $data
     * @return string
     */
    private function createMessage(array $data): string
    {
        $portion = Arr::get($data, 'portion', '');
        $servings = Arr::get($data, 'servings', '');
        $description = Arr::get($data, 'recipe_description', '');

        $ingredients = Arr::join($data['ingredients'] ?? [], "\n- ", '', '');
        $restrictions = Arr::join($data['restrictions'] ?? [], "\n- ", '', '');'';

        return "Create a variation of the following recipe.
        Portion: {$portion}
        Servings: {$servings}

        Recipe Description:
        {$description}

        Additional Ingredients:
        - {$ingredients}

        Dietary Restrictions to consider:
        - {$restrictions}

        Please provide a new recipe variation that fits these restrictions and uses the listed ingredients.";
    }
}
