<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class RecipeService
{
    public function generateRecipe(array $ingredients): string
    {
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

        return $response->json()['choices'][0]['message']['content'];
    }
}