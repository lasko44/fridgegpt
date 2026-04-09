<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RecipeIndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = auth()->user();

        $query = $user->recipe()->with('ingredients');

        // Search by name or ingredient
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('input_ingredients', 'ilike', "%{$search}%")
                  ->orWhereHas('ingredients', function ($iq) use ($search) {
                      $iq->where('name', 'ilike', "%{$search}%");
                  });
            });
        }

        // Filter: variations only
        if ($request->boolean('variations')) {
            $query->where('is_variation', true);
        }

        // Filter: originals only
        if ($request->boolean('originals')) {
            $query->where(function ($q) {
                $q->where('is_variation', false)->orWhereNull('is_variation');
            });
        }

        // Sort
        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'oldest' => $query->oldest(),
            'name' => $query->orderBy('name'),
            default => $query->latest(),
        };

        $recipes = $query->paginate(12)->withQueryString();

        return Inertia::render('Recipes', [
            'recipes' => $recipes,
            'tokenBalance' => $user->token_balance,
            'filters' => [
                'search' => $request->input('search', ''),
                'sort' => $sort,
                'variations' => $request->boolean('variations'),
                'originals' => $request->boolean('originals'),
            ],
        ]);
    }
}
