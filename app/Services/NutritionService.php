<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\NutritionCache;
use App\Models\Recipe;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NutritionService
{
    /**
     * Look up nutrition by barcode via Open Food Facts (free, no auth).
     */
    public function lookupByBarcode(string $barcode): ?array
    {
        $cached = NutritionCache::findByKey("barcode:{$barcode}");
        if ($cached) {
            return [
                'product_name' => $cached->product_name,
                'barcode' => $barcode,
                'nutrition' => $cached->nutrition,
                'source' => 'openfoodfacts',
            ];
        }

        try {
            $response = Http::timeout(10)->get(
                "https://world.openfoodfacts.org/api/v2/product/{$barcode}.json"
            );

            if (!$response->successful() || ($response->json('status') !== 1)) {
                return null;
            }

            $product = $response->json('product', []);
            $nutriments = $product['nutriments'] ?? [];

            $nutrition = [
                'calories' => round($nutriments['energy-kcal_100g'] ?? 0, 1),
                'protein' => round($nutriments['proteins_100g'] ?? 0, 1),
                'carbs' => round($nutriments['carbohydrates_100g'] ?? 0, 1),
                'fat' => round($nutriments['fat_100g'] ?? 0, 1),
                'fiber' => round($nutriments['fiber_100g'] ?? 0, 1),
            ];

            $productName = $product['product_name'] ?? $product['product_name_en'] ?? 'Unknown Product';
            $imageUrl = $product['image_front_small_url'] ?? $product['image_url'] ?? null;

            NutritionCache::create([
                'lookup_key' => "barcode:{$barcode}",
                'source' => 'openfoodfacts',
                'product_name' => $productName,
                'nutrition' => $nutrition,
            ]);

            return [
                'product_name' => $productName,
                'barcode' => $barcode,
                'nutrition' => $nutrition,
                'image_url' => $imageUrl,
                'source' => 'openfoodfacts',
            ];
        } catch (\Exception $e) {
            Log::warning('Open Food Facts lookup failed', ['barcode' => $barcode, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Look up nutrition by ingredient name via USDA FoodData Central.
     */
    public function lookupByIngredientName(string $name, ?string $amount = null, ?string $unit = null): ?array
    {
        $key = 'usda:' . strtolower(trim($name));
        $cached = NutritionCache::findByKey($key);
        if ($cached) {
            return $cached->nutrition;
        }

        try {
            $apiKey = config('services.usda.api_key', 'DEMO_KEY');
            $response = Http::timeout(10)->get(
                config('services.usda.base_url', 'https://api.nal.usda.gov/fdc/v1') . '/foods/search',
                ['query' => $name, 'api_key' => $apiKey, 'pageSize' => 1]
            );

            if (!$response->successful()) {
                return null;
            }

            $foods = $response->json('foods', []);
            if (empty($foods)) {
                return null;
            }

            $food = $foods[0];
            $nutrients = collect($food['foodNutrients'] ?? []);

            $nutrition = [
                'calories' => round($nutrients->firstWhere('nutrientName', 'Energy')['value'] ?? 0, 1),
                'protein' => round($nutrients->firstWhere('nutrientName', 'Protein')['value'] ?? 0, 1),
                'carbs' => round($nutrients->firstWhere('nutrientName', 'Carbohydrate, by difference')['value'] ?? 0, 1),
                'fat' => round($nutrients->firstWhere('nutrientName', 'Total lipid (fat)')['value'] ?? 0, 1),
                'fiber' => round($nutrients->firstWhere('nutrientName', 'Fiber, total dietary')['value'] ?? 0, 1),
            ];

            NutritionCache::create([
                'lookup_key' => $key,
                'source' => 'usda',
                'product_name' => $food['description'] ?? $name,
                'nutrition' => $nutrition,
            ]);

            return $nutrition;
        } catch (\Exception $e) {
            Log::warning('USDA lookup failed', ['ingredient' => $name, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Estimate weight in grams from amount + unit.
     */
    private function estimateGrams(?string $amount, ?string $unit, string $name): float
    {
        $qty = (float) ($amount ?: 1);
        $u = strtolower(trim($unit ?? ''));
        $n = strtolower($name);

        // Common unit conversions to grams
        $unitToGrams = [
            'g' => 1, 'gram' => 1, 'grams' => 1,
            'kg' => 1000, 'kilogram' => 1000,
            'oz' => 28.35, 'ounce' => 28.35, 'ounces' => 28.35,
            'lb' => 453.6, 'lbs' => 453.6, 'pound' => 453.6, 'pounds' => 453.6,
            'cup' => 240, 'cups' => 240,
            'tablespoon' => 15, 'tablespoons' => 15, 'tbsp' => 15,
            'teaspoon' => 5, 'teaspoons' => 5, 'tsp' => 5,
            'ml' => 1, 'milliliter' => 1,
            'liter' => 1000, 'l' => 1000,
        ];

        if (isset($unitToGrams[$u])) {
            return $qty * $unitToGrams[$u];
        }

        // Estimate by food type for "pieces", "whole", "medium", "cloves", etc.
        $pieceWeights = [
            'chicken breast' => 175, 'chicken thigh' => 120, 'chicken' => 150,
            'egg' => 50, 'eggs' => 50,
            'onion' => 150, 'garlic' => 3, // per clove
            'tomato' => 150, 'potato' => 170, 'carrot' => 70,
            'lemon' => 60, 'lime' => 45, 'apple' => 180, 'banana' => 120,
            'bell pepper' => 120, 'pepper' => 120,
            'avocado' => 150,
            'slice' => 30, 'strip' => 20,
            'tortilla' => 50, 'bread' => 30,
            'salmon fillet' => 170, 'salmon' => 170,
            'shrimp' => 10, // per piece
            'mushroom' => 15,
            'tofu' => 350, // per block
        ];

        foreach ($pieceWeights as $keyword => $weight) {
            if (str_contains($n, $keyword)) {
                return $qty * $weight;
            }
        }

        // Default: assume ~100g per "piece"
        return $qty * 100;
    }

    /**
     * Scale per-100g nutrition to actual ingredient amount.
     */
    private function scaleNutrition(array $per100g, float $grams): array
    {
        $factor = $grams / 100;
        return [
            'calories' => round(($per100g['calories'] ?? 0) * $factor, 1),
            'protein' => round(($per100g['protein'] ?? 0) * $factor, 1),
            'carbs' => round(($per100g['carbs'] ?? 0) * $factor, 1),
            'fat' => round(($per100g['fat'] ?? 0) * $factor, 1),
            'fiber' => round(($per100g['fiber'] ?? 0) * $factor, 1),
        ];
    }

    /**
     * Refine all ingredient nutrition for a recipe using USDA data.
     */
    public function refineRecipeNutrition(Recipe $recipe): void
    {
        $recipe->load('ingredients');

        foreach ($recipe->ingredients as $ingredient) {
            $per100g = $this->lookupByIngredientName(
                $ingredient->name,
                $ingredient->amount,
                $ingredient->unit
            );

            if ($per100g) {
                $grams = $this->estimateGrams($ingredient->amount, $ingredient->unit, $ingredient->name);
                $scaled = $this->scaleNutrition($per100g, $grams);

                $ingredient->update([
                    'calories' => $scaled['calories'],
                    'protein' => $scaled['protein'],
                    'carbs' => $scaled['carbs'],
                    'fat' => $scaled['fat'],
                    'fiber' => $scaled['fiber'],
                    'nutrition_source' => 'usda',
                ]);
            }
        }

        $recipe->recalculateNutrition();

        Log::info('Recipe nutrition refined', ['recipe_id' => $recipe->id]);
    }
}
