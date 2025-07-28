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
        $isGuest = !$user;
        $ingredients = $request->input('ingredients');
        $ip = $request->ip();

        try {
            if ($isGuest) {

                $recipe = RecipeUtil::guestStore($ingredients, $ip);

                return redirect()->route('home')->with([
                    'recipe' => $recipe->get(),
                    'recipes' => RecipeUtil::getGuestRecipes($ip)
                ]);
            }
            if ($user && !$user->is_subscribed) {
                $recipe = RecipeUtil::standardStore($ingredients, $user);

                return redirect()->route('home')->with([
                    'recipe' => $recipe->get(),
                    'recipes' => RecipeUtil::getStandardRecipes($user)
                ]);
            }
        } catch (Exception $e) {
            return redirect()->route('home')->withErrors([
                'error' => $e->getMessage()
            ]);
        }

        $recipe = RecipeUtil::generateRecipe($ingredients);

        return redirect()->route('home')->with([
            'paginated' => true,
            'recipe' => $recipe->get(),
            'recipes' => RecipeUtil::getPremiumRecipes($user)
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
