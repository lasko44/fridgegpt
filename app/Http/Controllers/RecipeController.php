<?php

namespace App\Http\Controllers;

use App\Facades\RecipeUtil;
use App\Models\Recipe;
use App\Services\RecipeRateLimiter;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class RecipeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): object
    {
        $user = $request->user();
        $isGuest = !$user;

        if ($isGuest) {
            $cacheKey = 'guest_recipes_' . $request->ip();
            $recipes = Cache::get($cacheKey, []);
            if (count($recipes) >= 3) {
                return response()->json([
                    'error' => 'Daily limit reached. Please try again tomorrow.'
                ], ResponseAlias::HTTP_TOO_MANY_REQUESTS);
            }

            $ingredients = $request->input('ingredients');
            $recipe = RecipeUtil::generateRecipe($ingredients)->get();

            $recipes[] = $recipe;
            Cache::put($cacheKey, $recipes, now()->addDay());

            return redirect()->route('home')->with('recipe', $recipe);
        }
        $ingredients = $request->input('ingredients');
        $recipe = RecipeUtil::generateRecipe($ingredients)->get();


        return redirect()->route('home')->with('recipe', '$recipe');
    }

    /**
     * Display the specified resource.
     */
    public function show(Recipe $recipe)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Recipe $recipe)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Recipe $recipe)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Recipe $recipe)
    {
        //
    }
}
