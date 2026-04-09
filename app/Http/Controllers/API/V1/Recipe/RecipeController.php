<?php

namespace App\Http\Controllers\Api\V1\Recipe;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recipe\StoreRecipeRequest;
use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use App\Services\IngredientSuggestionService;
use App\Services\RecipeService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * API controller for handling recipe operations.
 */
class RecipeController extends Controller
{
    /**
     * Display a listing of the user's recipes.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $recipes = $user->recipe()
            ->with('ingredients')
            ->latest()
            ->paginate(10);

        return RecipeResource::collection($recipes);
    }

    /**
     * Store a newly created recipe.
     */
    public function store(StoreRecipeRequest $request, RecipeService $recipeService): JsonResponse
    {
        try {
            $result = $recipeService->createRecipeForUser(
                $request->getIngredients(),
                $request->user(),
                $request->ip(),
                $request->getRestrictions(),
                $request->only(['servings', 'portion', 'include_staples', 'allow_extras'])
            );

            // Get the latest recipe for the user
            $recipe = $request->user()->recipe()
                ->with('ingredients')
                ->latest()
                ->first();

            return response()->json([
                'message' => 'Recipe created successfully!',
                'recipe' => $recipe ? new RecipeResource($recipe) : null,
                'raw_recipe' => $result['recipe'],
                'structured' => $result['structured'] ?? null,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Store a recipe for guest users (rate limited).
     */
    public function guestStore(StoreRecipeRequest $request, RecipeService $recipeService): JsonResponse
    {
        try {
            $result = $recipeService->createRecipeForUser(
                $request->getIngredients(),
                null,
                $request->ip(),
                $request->getRestrictions()
            );

            return response()->json([
                'message' => 'Recipe created successfully!',
                'raw_recipe' => $result['recipe'],
                'structured' => $result['structured'] ?? null,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display the specified recipe.
     */
    public function show(Recipe $recipe): RecipeResource
    {
        $recipe->load('ingredients');

        return new RecipeResource($recipe);
    }

    /**
     * Remove the specified recipe.
     */
    public function destroy(Request $request, Recipe $recipe): JsonResponse
    {
        Gate::authorize('delete', $recipe);

        $recipe->delete();

        return response()->json([
            'message' => 'Recipe deleted successfully.',
        ]);
    }

    /**
     * Suggest ingredients based on current selection using RAG.
     */
    public function suggestIngredients(Request $request, IngredientSuggestionService $service): JsonResponse
    {
        $ingredients = $request->input('ingredients', []);

        $suggestions = $service->suggest(
            is_array($ingredients) ? $ingredients : [],
            8
        );

        return response()->json([
            'suggestions' => $suggestions,
        ]);
    }
}
