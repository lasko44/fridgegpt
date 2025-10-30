<?php

namespace App\Http\Controllers;

use App\Facades\RecipeUtil;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        //get the timezone from the browser
        $timezone = request()->query('timezone');

        Cache::put('user_timezone', $timezone, 60 * 24); // Store for 24 hours
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