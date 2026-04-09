<?php

namespace App\Http\Controllers;

use App\Http\Requests\Recipe\StoreRecipeRequest;
use App\Models\Recipe;
use App\Services\RecipeService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller for handling recipe-related web requests.
 */
class RecipeController extends Controller
{
    /**
     * Store a newly created recipe in storage.
     */
    public function store(StoreRecipeRequest $request, RecipeService $recipeService): RedirectResponse
    {
        try {
            $result = $recipeService->createRecipeForUser(
                $request->getIngredients(),
                $request->user(),
                $request->ip(),
                $request->getRestrictions(),
                $request->only(['servings', 'portion', 'include_staples', 'allow_extras'])
            );

            return redirect()->route('home')
                ->with('recipe', $result['recipe'])
                ->with('structured', $result['structured'] ?? null)
                ->with('flash', $result['flash'] ?? []);
        } catch (Exception $e) {
            return redirect()->route('home')->withErrors([
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Display the specified recipe.
     */
    public function show(Recipe $recipe): Response
    {
        Gate::authorize('view', $recipe);
        $recipe->load('ingredients');

        // Load parent recipe info for variations
        $parentRecipe = null;
        if ($recipe->is_variation && $recipe->recipe_id) {
            $parent = Recipe::find($recipe->recipe_id);
            if ($parent) {
                $parentRecipe = ['name' => $parent->name, 'slug' => $parent->slug];
            }
        }

        return Inertia::render('RecipeShow', [
            'recipe' => array_merge($recipe->toArray(), [
                'id' => $recipe->id,
                'is_variation' => (bool) $recipe->is_variation,
                'parent_recipe' => $parentRecipe,
            ]),
        ]);
    }

    /**
     * Remove the specified recipe from storage.
     */
    public function destroy(Recipe $recipe): RedirectResponse
    {
        Gate::authorize('delete', $recipe);

        $recipe->delete();

        return redirect()->route('home')->with('success', 'Recipe deleted successfully.');
    }
}
