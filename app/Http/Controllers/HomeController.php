<?php

namespace App\Http\Controllers;

use App\Facades\RecipeUtil;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();

        $recipes = !$user ? session('recipe') :
            ($user->is_subscribed ? RecipeUtil::getPremiumRecipes($user)
                : RecipeUtil::getStandardRecipes($user));
        return Inertia::render('Home', [
            'recipe' => session('recipe'),
            'recipes' => $recipes,
        ]);
    }
}
