<?php

namespace App\Http\Controllers;

use App\Models\MealPlan;
use App\Services\KrogerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Web endpoints for grocery store lookup and pricing.
 */
class GroceryController extends Controller
{
    public function stores(Request $request, KrogerService $kroger): JsonResponse
    {
        $validated = $request->validate([
            'zip_code' => ['required', 'string', 'regex:/^\d{5}$/'],
        ]);

        // Save zip code so future meal plans use area-accurate price estimates
        $request->user()->update(['zip_code' => $validated['zip_code']]);

        return response()->json([
            'stores' => $kroger->findStores($validated['zip_code']),
        ]);
    }

    public function price(Request $request, KrogerService $kroger): JsonResponse
    {
        $validated = $request->validate([
            'meal_plan_uuid' => ['required', 'string', 'exists:meal_plans,uuid'],
            'location_id' => ['required', 'string'],
            'store_name' => ['nullable', 'string'],
        ]);

        $plan = MealPlan::where('uuid', $validated['meal_plan_uuid'])->firstOrFail();

        if ($plan->user_id !== $request->user()->id) {
            abort(404);
        }

        // Save as preferred store for future auto-pricing
        $request->user()->update([
            'preferred_store_id' => $validated['location_id'],
            'preferred_store_name' => $validated['store_name'] ?? 'Kroger',
        ]);

        $result = $kroger->priceGroceryList(
            $plan->grocery_list ?? [],
            $validated['location_id'],
            ['name' => $validated['store_name'] ?? 'Kroger', 'locationId' => $validated['location_id']],
        );

        $plan->update([
            'grocery_list' => $result['items'],
            'grocery_total_cents' => $result['total_cents'],
        ]);

        return response()->json($result);
    }
}
