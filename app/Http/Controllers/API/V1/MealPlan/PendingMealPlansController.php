<?php

namespace App\Http\Controllers\Api\V1\MealPlan;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Returns the user's most recently updated meal plans for client-side polling.
 * Used by the global toast watcher to detect when generation completes.
 */
class PendingMealPlansController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $plans = $request->user()->mealPlans()
            ->select(['id', 'uuid', 'name', 'status', 'updated_at'])
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(fn ($plan) => [
                'uuid' => $plan->uuid,
                'name' => $plan->name,
                'status' => $plan->status,
                'updated_at' => $plan->updated_at,
            ]);

        return response()->json(['plans' => $plans]);
    }
}
