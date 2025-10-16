<?php

namespace App\Http\Controllers;

use App\Facades\RecipeUtil;
use App\Models\Recipe;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

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
                $data = [
                    'recipe' => $recipe->get(),
                    'flash' => ['success' => 'Recipe created successfully!']
                ];
            } elseif (!$user->is_subscribed) {
                $recipe = RecipeUtil::standardStore($ingredients, $user);
                $recipes = RecipeUtil::getStandardRecipes($user);
                $data = [
                    'recipe' => $recipe->get(),
                    'flash' => ['success' => 'Recipe created successfully!']
                ];
            } else {
                $recipe = RecipeUtil::premiumStore($ingredients, $user);
                $recipes = RecipeUtil::getPremiumRecipes($user);
                $data = [
                    'paginated' => true,
                    'recipe' => $recipe->get(),
                    'flash' => ['success' => 'Recipe created successfully!']
                ];
            }

            return redirect()->route('home')->with($data);
        } catch (Exception $e) {
            return redirect()->route('home')->withErrors([
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Recipe $recipe): Response
    {
        $recipe->load('ingredients');

        return Inertia::render('RecipeShow', [
            'recipe' => $recipe,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($user, Recipe $recipe = null)
    {

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Recipe $recipe)
    {
        //implement the update logic here
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Recipe $recipe)
    {
        //
    }
}
