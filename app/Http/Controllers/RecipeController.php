<?php

namespace App\Http\Controllers;

use App\Facades\RecipeUtil;
use App\Models\Recipe;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

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
        $ingredients = $request->input('ingredients');
        $ip = $request->ip();

        try {
            if (!$user) {
                $recipe = RecipeUtil::guestStore($ingredients, $ip);
                $recipes = RecipeUtil::getGuestRecipes($ip);
            } elseif (!$user->is_subscribed) {
                $recipe = RecipeUtil::standardStore($ingredients, $user);
                $recipes = RecipeUtil::getStandardRecipes($user);
            } else {
                $recipe = RecipeUtil::premiumStore($ingredients, $user);
                $recipes = RecipeUtil::getPremiumRecipes($user);
                return redirect()->route('home')->with([
                    'paginated' => true,
                    'recipe' => $recipe->get(),
                    'recipes' => $recipes
                ]);
            }

            return redirect()->route('home')->with([
                'recipe' => $recipe->get(),
                'recipes' => $recipes
            ]);
        } catch (Exception $e) {
            return redirect()->route('home')->withErrors([
                'error' => $e->getMessage()
            ]);
        }
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
