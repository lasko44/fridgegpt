<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin content management for recipes.
 */
class RecipeController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Recipe::query()
            ->with('user:id,uuid,name,email')
            ->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('input_ingredients', 'ilike', "%{$search}%");
            });
        }

        $recipes = $query->paginate(25)->withQueryString();

        return Inertia::render('Admin/Recipes/Index', [
            'recipes' => $recipes,
            'filters' => ['search' => $search],
        ]);
    }

    public function destroy(Recipe $recipe): RedirectResponse
    {
        $recipe->delete();

        return back()->with('flash', ['success' => 'Recipe deleted']);
    }
}
