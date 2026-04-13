<?php

namespace App\Http\Controllers\Api\V1\Grocery;

use App\Http\Controllers\Controller;
use App\Models\MealPlan;
use App\Services\KrogerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Looks up real grocery prices from Kroger for a meal plan's grocery list.
 */
class GroceryPriceController extends Controller
{
    public function stores(Request $request, KrogerService $kroger): JsonResponse
    {
        $validated = $request->validate([
            'zip_code' => ['required', 'string', 'regex:/^\d{5}$/'],
        ]);

        $stores = $kroger->findStores($validated['zip_code']);

        return response()->json(['stores' => $stores]);
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

        $items = $plan->grocery_list ?? [];

        if (empty($items)) {
            return response()->json([
                'items' => [],
                'total_cents' => 0,
                'store' => ['name' => $validated['store_name'] ?? 'Kroger'],
            ]);
        }

        $result = $kroger->priceGroceryList(
            $items,
            $validated['location_id'],
            ['name' => $validated['store_name'] ?? 'Kroger', 'locationId' => $validated['location_id']],
        );

        // Save the Kroger prices back to the meal plan
        $plan->update([
            'grocery_list' => $result['items'],
            'grocery_total_cents' => $result['total_cents'],
        ]);

        return response()->json($result);
    }
}
