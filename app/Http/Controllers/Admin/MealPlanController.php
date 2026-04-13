<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MealPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin content management for meal plans.
 */
class MealPlanController extends Controller
{
    public function index(Request $request): Response
    {
        $query = MealPlan::query()
            ->with('user:id,uuid,name,email')
            ->withCount('slots')
            ->latest();

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where('name', 'ilike', "%{$search}%");
        }

        $plans = $query->paginate(25)->withQueryString();

        return Inertia::render('Admin/MealPlans/Index', [
            'plans' => $plans,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function destroy(MealPlan $mealPlan): RedirectResponse
    {
        $mealPlan->delete();

        return back()->with('flash', ['success' => 'Meal plan deleted']);
    }
}
