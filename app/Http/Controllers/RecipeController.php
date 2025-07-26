<?php

namespace App\Http\Controllers;

use App\Facades\RecipeUtil;
use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class RecipeController extends Controller
{

    private const GUEST_LIMIT_ERROR = 'Daily limit reached. Please try again tomorrow.';
    private const SUBSCRIPTION_LIMIT_ERROR = 'You have reached your daily recipe limit. 
    Please subscribe to get more recipes.';

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
        $ingredients = $request->input('ingredients');

        if ($isGuest) {

            $cacheKey = 'guest_recipes_' . $request->ip();
            $recipes = Cache::get($cacheKey, []);
            if (count($recipes) >= 3) {
                return redirect()->route('home')->withErrors([
                    'guest_limit' => self::GUEST_LIMIT_ERROR
                ]);
            }
            $recipe = RecipeUtil::generateRecipe($ingredients);

            $recipes[Str::random(8)] = $recipe->toArray();
            Cache::put($cacheKey, $recipes, now()->addDay());

            return redirect()->route('home')->with([
                'recipe' => $recipe->get(),
                'recipes' => $recipes
            ]);
        }
        if ($user && !$user->is_subscribed) {
            if ($user->dayRecipeCount() > 2) {
                return redirect()->route('home')->withErrors(
                    [
                        'subscription_limit' => self::SUBSCRIPTION_LIMIT_ERROR
                    ]
                );
            }

            $recipe = RecipeUtil::generateRecipe($ingredients);

            if ($user->recipeCount() > 4) {
                $user->deleteOldestRecipe();
            }

            //Create the recipe in the database
            $recipeArray = $recipe->toArray();

            //get user's latest 5 recipes
            $recipes = $user->recipe()->latest()->take(5)->get();

            return redirect()->route('home')->with([
                'recipe' => $recipe->get(),
                'recipes' => $recipes
            ]);
        }

        $recipe = RecipeUtil::generateRecipe($ingredients);

        $recipeArray = $recipe->toArray();

        //store new recipe in the database


        //get all user recipes and paginate them
        $recipes = $user->recipe()->latest()->paginate(5);

        return redirect()->route('home')->with([
            'recipe' => $recipe->get(),
            'recipes' => $recipes,
        ]);
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
