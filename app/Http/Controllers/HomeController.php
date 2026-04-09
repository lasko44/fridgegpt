<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $timezone = request()->query('timezone');

        if ($timezone && in_array($timezone, \DateTimeZone::listIdentifiers())) {
            Cache::put('user_timezone', $timezone, 60 * 24);
        }

        $user = auth()->user();

        // All authenticated users see their recipes paginated
        $recipes = $user
            ? $user->recipe()->with('ingredients')->latest()->paginate(10)
            : null;

        return Inertia::render('Home', [
            'recipe' => session('recipe'),
            'structured' => session('structured'),
            'recipes' => $recipes,
            'tokenBalance' => $user?->token_balance ?? 0,
            'tokenCost' => config('tokens.costs.recipe', 1),
        ]);
    }
}
