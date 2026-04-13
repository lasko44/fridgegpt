<?php

namespace App\Http\Controllers;

use App\Models\MealPlan;
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

        if (! $user) {
            return Inertia::render('Home', [
                'recipe' => null,
                'structured' => null,
                'recipes' => null,
                'mealPlans' => null,
                'stats' => null,
                'tokenBalance' => 0,
                'tokenCost' => config('tokens.costs.recipe', 1),
            ]);
        }

        $recentRecipes = $user->recipe()
            ->latest()
            ->limit(5)
            ->get(['id', 'name', 'slug', 'calories_per_serving', 'created_at']);

        $recentPlans = $user->mealPlans()
            ->latest()
            ->limit(3)
            ->get(['id', 'uuid', 'name', 'days', 'status', 'grocery_total_cents', 'tokens_spent', 'created_at']);

        $activePlan = $user->mealPlans()
            ->whereIn('status', ['generating', 'draft'])
            ->latest()
            ->first(['id', 'uuid', 'name', 'status']);

        $stats = [
            'total_recipes' => $user->recipe()->count(),
            'total_meal_plans' => $user->mealPlans()->count(),
            'recipes_this_week' => $user->recipe()->where('created_at', '>=', now()->subDays(7))->count(),
        ];

        return Inertia::render('Home', [
            'recipe' => session('recipe'),
            'structured' => session('structured'),
            'recipes' => $recentRecipes,
            'mealPlans' => $recentPlans,
            'activePlan' => $activePlan,
            'stats' => $stats,
            'tokenBalance' => $user->token_balance,
            'tokenCost' => config('tokens.costs.recipe', 1),
            'userStoreName' => $user->preferred_store_name,
        ]);
    }
}
