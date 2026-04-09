<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IngredientSuggestionService
{
    private EmbeddingService $embeddingService;

    public function __construct()
    {
        $this->embeddingService = new EmbeddingService();
    }

    /**
     * Suggest ingredients based on what the user has already added,
     * using vector similarity to find recipes with similar ingredient profiles.
     *
     * @param array<string> $currentIngredients
     * @param int $limit
     * @return array<string>
     */
    public function suggest(array $currentIngredients, int $limit = 8): array
    {
        if (empty($currentIngredients)) {
            return $this->getPopularIngredients($limit);
        }

        try {
            $ingredientList = implode(', ', $currentIngredients);
            $embedding = $this->embeddingService->embed("Recipe ingredients: " . $ingredientList);
            $vector = '[' . implode(',', $embedding) . ']';

            // Find similar recipes and extract their ingredients
            $results = DB::select(
                "SELECT input_ingredients
                 FROM recipes
                 WHERE embedding IS NOT NULL
                 AND deleted_at IS NULL
                 AND input_ingredients IS NOT NULL
                 ORDER BY embedding <=> ?::vector
                 LIMIT 10",
                [$vector]
            );

            if (empty($results)) {
                return $this->getPopularIngredients($limit);
            }

            // Extract unique ingredients from similar recipes, excluding ones already added
            $currentLower = array_map('strtolower', $currentIngredients);
            $suggestions = [];
            $counts = [];

            foreach ($results as $row) {
                $recipeIngredients = array_map('trim', explode(',', $row->input_ingredients));
                foreach ($recipeIngredients as $ingredient) {
                    $lower = strtolower($ingredient);
                    if (!in_array($lower, $currentLower) && strlen($ingredient) > 1) {
                        $counts[$ingredient] = ($counts[$ingredient] ?? 0) + 1;
                    }
                }
            }

            // Sort by frequency (most common pairings first)
            arsort($counts);

            return array_slice(array_keys($counts), 0, $limit);
        } catch (\Exception $e) {
            Log::warning('RAG ingredient suggestion failed', ['error' => $e->getMessage()]);
            return $this->getPopularIngredients($limit);
        }
    }

    /**
     * Fallback: return commonly used ingredients from the database.
     */
    private function getPopularIngredients(int $limit): array
    {
        try {
            $results = DB::select(
                "SELECT name, COUNT(*) as count
                 FROM ingredients
                 GROUP BY name
                 ORDER BY count DESC
                 LIMIT ?",
                [$limit]
            );

            if (!empty($results)) {
                return array_map(fn($r) => $r->name, $results);
            }
        } catch (\Exception $e) {
            // Fall through to hardcoded defaults
        }

        return ['Chicken', 'Rice', 'Pasta', 'Eggs', 'Garlic', 'Onion', 'Tomato', 'Cheese'];
    }
}
