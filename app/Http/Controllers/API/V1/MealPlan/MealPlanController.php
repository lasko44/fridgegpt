<?php

namespace App\Http\Controllers\Api\V1\MealPlan;

use App\Http\Controllers\Controller;
use App\Http\Requests\MealPlan\CreateMealPlanRequest;
use App\Jobs\GenerateMealPlanJob;
use App\Models\MealPlan;
use App\Models\MealPlanSlot;
use App\Services\MealPlanService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MealPlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->mealPlans();

        // Search by name
        if ($search = $request->input('search')) {
            $query->where('name', 'ilike', "%{$search}%");
        }

        // Filter by status
        if ($status = $request->input('status')) {
            if ($status === 'generating') {
                $query->whereIn('status', ['draft', 'generating']);
            } else {
                $query->where('status', $status);
            }
        }

        // Sort
        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'oldest' => $query->oldest(),
            'name' => $query->orderBy('name'),
            'budget' => $query->orderByDesc('grocery_total_cents'),
            default => $query->latest(),
        };

        $plans = $query->paginate(10);

        return response()->json([
            'plans' => $plans,
            'tokenBalance' => $request->user()->token_balance,
        ]);
    }

    public function store(CreateMealPlanRequest $request, MealPlanService $service): JsonResponse
    {
        try {
            $plan = $service->createPlan($request->user(), $request->validated());

            GenerateMealPlanJob::dispatch($plan);

            return response()->json([
                'message' => 'Meal plan is being generated!',
                'plan' => $plan,
            ], 201);
        } catch (Exception $e) {
            Log::error('API Meal plan creation failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function show(MealPlan $mealPlan): JsonResponse
    {
        $mealPlan->load('slots.recipe');

        $dailyNutrition = [];
        for ($d = 1; $d <= $mealPlan->days; $d++) {
            $dailyNutrition[$d] = $mealPlan->dailyNutrition($d);
        }

        return response()->json([
            'plan' => $mealPlan,
            'dailyNutrition' => $dailyNutrition,
        ]);
    }

    public function destroy(Request $request, MealPlan $mealPlan): JsonResponse
    {
        if ($mealPlan->user_id !== $request->user()->id) {
            abort(404);
        }

        $mealPlan->delete();

        return response()->json(['message' => 'Meal plan deleted']);
    }

    public function regenerateSlot(MealPlan $mealPlan, MealPlanSlot $slot, Request $request, MealPlanService $service): JsonResponse
    {
        try {
            $updatedSlot = $service->regenerateSlot($slot, $request->user());

            return response()->json([
                'message' => 'Meal regenerated!',
                'slot' => $updatedSlot,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
