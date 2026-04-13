<?php

namespace App\Http\Controllers\MealPlan;

use App\Http\Controllers\Controller;
use App\Http\Requests\MealPlan\CreateMealPlanRequest;
use App\Jobs\GenerateMealPlanJob;
use App\Models\MealPlan;
use App\Models\MealPlanSlot;
use App\Services\MealPlanService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class MealPlanController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();
        $query = $user->mealPlans();

        // Search by name
        if ($search = request()->input('search')) {
            $query->where('name', 'ilike', "%{$search}%");
        }

        // Filter by status
        if ($status = request()->input('status')) {
            if ($status === 'generating') {
                $query->whereIn('status', ['draft', 'generating']);
            } else {
                $query->where('status', $status);
            }
        }

        // Sort
        $sort = request()->input('sort', 'newest');
        match ($sort) {
            'oldest' => $query->oldest(),
            'name' => $query->orderBy('name'),
            'budget' => $query->orderByDesc('grocery_total_cents'),
            default => $query->latest(),
        };

        $plans = $query->paginate(10)->withQueryString();

        return Inertia::render('MealPlans', [
            'plans' => $plans,
            'tokenBalance' => $user->token_balance,
            'filters' => [
                'search' => request()->input('search', ''),
                'sort' => $sort,
                'status' => request()->input('status', ''),
            ],
        ]);
    }

    public function create(): Response
    {
        $user = auth()->user();
        $savedRecipeCount = $user->recipe()->whereNotNull('calories_per_serving')->count();

        return Inertia::render('MealPlanCreate', [
            'tokenBalance' => $user->token_balance,
            'savedRecipeCount' => $savedRecipeCount,
            'userZipCode' => $user->zip_code,
            'userStoreName' => $user->preferred_store_name,
        ]);
    }

    public function store(CreateMealPlanRequest $request, MealPlanService $service): RedirectResponse
    {
        try {
            // Save user's location preferences for auto-pricing
            $user = $request->user();
            $validated = $request->validated();
            $updates = [];
            if (! empty($validated['zip_code'])) {
                $updates['zip_code'] = $validated['zip_code'];
            }
            if (! empty($validated['store_id'])) {
                $updates['preferred_store_id'] = $validated['store_id'];
                $updates['preferred_store_name'] = $validated['store_name'] ?? 'Kroger';
            }
            if (! empty($updates)) {
                $user->update($updates);
            }

            $plan = $service->createPlan($user, $validated);

            GenerateMealPlanJob::dispatch($plan);

            return redirect()->route('meal-plans.show', $plan->uuid);
        } catch (Exception $e) {
            Log::error('Meal plan creation failed', ['error' => $e->getMessage()]);
            return back()
                ->withErrors(['error' => $e->getMessage()])
                ->with('flash', ['error' => $e->getMessage()]);
        }
    }

    public function show(MealPlan $mealPlan): Response
    {
        $mealPlan->load('slots.recipe');

        // Shape slots into day-grouped structure the frontend expects
        $daySlots = [];
        $dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        for ($d = 1; $d <= $mealPlan->days; $d++) {
            $slots = $mealPlan->slots->where('day_number', $d);
            $meals = [];

            foreach ($slots as $slot) {
                $nutrition = $slot->recipe_data['nutrition_per_serving'] ?? null;
                $meals[] = [
                    'meal_type' => $slot->meal_type,
                    'recipe_name' => $slot->recipe_name,
                    'recipe_slug' => $slot->recipe?->slug,
                    'calories' => $nutrition['calories'] ?? ($slot->recipe?->calories_per_serving),
                ];
            }

            $daySlots[] = [
                'day_number' => $d,
                'day_label' => $dayNames[($d - 1) % 7],
                'meals' => $meals,
            ];
        }

        // Build daily nutrition with targets
        $dailyNutrition = [];
        for ($d = 1; $d <= $mealPlan->days; $d++) {
            $raw = $mealPlan->dailyNutrition($d);
            $dailyNutrition[$d] = [
                'calories' => $raw['calories'] ?? 0,
                'protein' => $raw['protein'] ?? 0,
                'carbs' => $raw['carbs'] ?? 0,
                'fat' => $raw['fat'] ?? 0,
                'targets' => [
                    'calories' => $mealPlan->target_calories,
                    'protein' => $mealPlan->target_protein,
                    'carbs' => $mealPlan->target_carbs,
                    'fat' => $mealPlan->target_fat,
                ],
            ];
        }

        // Merge shaped data into plan for frontend
        $planData = $mealPlan->toArray();
        $planData['slots'] = $daySlots;
        $planData['grocery_list'] = $planData['grocery_list'] ?? [];

        return Inertia::render('MealPlanShow', [
            'plan' => $planData,
            'dailyNutrition' => $dailyNutrition,
            'tokenBalance' => auth()->user()->token_balance,
            'userZipCode' => auth()->user()->zip_code,
        ]);
    }

    public function destroy(MealPlan $mealPlan): RedirectResponse
    {
        if ($mealPlan->user_id !== auth()->id()) {
            abort(404);
        }

        $mealPlan->delete();

        return redirect()->route('meal-plans.index');
    }

    public function regenerateSlot(MealPlan $mealPlan, MealPlanSlot $slot, MealPlanService $service): RedirectResponse
    {
        try {
            $service->regenerateSlot($slot, auth()->user());

            return back()->with('flash', ['success' => 'Meal regenerated!']);
        } catch (Exception $e) {
            return back()
                ->withErrors(['error' => $e->getMessage()])
                ->with('flash', ['error' => $e->getMessage()]);
        }
    }
}
