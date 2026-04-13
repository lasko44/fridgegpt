<?php

namespace App\Services;

use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\TokenTransaction;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates platform-wide statistics for the admin dashboard.
 */
class AdminStatsService
{
    public function dashboard(): array
    {
        return [
            'users' => $this->userStats(),
            'tokens' => $this->tokenStats(),
            'content' => $this->contentStats(),
            'system' => $this->systemStats(),
            'charts' => $this->charts(),
            'topUsers' => $this->topUsers(),
        ];
    }

    private function userStats(): array
    {
        $now = now();

        return [
            'total' => User::count(),
            'admins' => User::where('is_admin', true)->count(),
            'verified' => User::whereNotNull('email_verified_at')->count(),
            'new_today' => User::where('created_at', '>=', $now->copy()->startOfDay())->count(),
            'new_this_week' => User::where('created_at', '>=', $now->copy()->subDays(7))->count(),
            'new_this_month' => User::where('created_at', '>=', $now->copy()->subDays(30))->count(),
            'active_7d' => $this->activeUsers(7),
            'active_30d' => $this->activeUsers(30),
        ];
    }

    private function activeUsers(int $days): int
    {
        // A user is "active" if they generated a recipe or meal plan in the last N days
        $since = now()->subDays($days);

        $recipeUserIds = Recipe::where('created_at', '>=', $since)->distinct()->pluck('user_id');
        $mealPlanUserIds = MealPlan::where('created_at', '>=', $since)->distinct()->pluck('user_id');

        return $recipeUserIds->merge($mealPlanUserIds)->unique()->count();
    }

    private function tokenStats(): array
    {
        $now = now();

        return [
            'total_balance' => User::sum('token_balance'),
            'purchased_today' => $this->tokensByType('purchase', $now->copy()->startOfDay()),
            'purchased_this_week' => $this->tokensByType('purchase', $now->copy()->subDays(7)),
            'purchased_this_month' => $this->tokensByType('purchase', $now->copy()->subDays(30)),
            'spent_today' => abs($this->tokensByType('spend', $now->copy()->startOfDay())),
            'spent_this_week' => abs($this->tokensByType('spend', $now->copy()->subDays(7))),
            'spent_this_month' => abs($this->tokensByType('spend', $now->copy()->subDays(30))),
            'revenue_today_cents' => $this->revenueSince($now->copy()->startOfDay()),
            'revenue_this_week_cents' => $this->revenueSince($now->copy()->subDays(7)),
            'revenue_this_month_cents' => $this->revenueSince($now->copy()->subDays(30)),
        ];
    }

    private function tokensByType(string $type, Carbon $since): int
    {
        return (int) TokenTransaction::where('type', $type)
            ->where('created_at', '>=', $since)
            ->sum('amount');
    }

    private function revenueSince(Carbon $since): int
    {
        // Revenue is calculated from token_packages joined via metadata->package_id.
        // Sum the price_cents of every purchase transaction since $since.
        $purchases = TokenTransaction::where('type', 'purchase')
            ->where('created_at', '>=', $since)
            ->get(['metadata']);

        $packageIds = $purchases
            ->map(fn ($t) => $t->metadata['package_id'] ?? null)
            ->filter()
            ->countBy();

        if ($packageIds->isEmpty()) {
            return 0;
        }

        $packagePrices = \App\Models\TokenPackage::whereIn('id', $packageIds->keys())
            ->pluck('price_cents', 'id');

        return (int) $packageIds->sum(
            fn ($count, $id) => ($packagePrices[$id] ?? 0) * $count
        );
    }

    private function contentStats(): array
    {
        $now = now();

        return [
            'total_recipes' => Recipe::count(),
            'recipes_today' => Recipe::where('created_at', '>=', $now->copy()->startOfDay())->count(),
            'recipes_this_week' => Recipe::where('created_at', '>=', $now->copy()->subDays(7))->count(),
            'total_meal_plans' => MealPlan::count(),
            'meal_plans_this_week' => MealPlan::where('created_at', '>=', $now->copy()->subDays(7))->count(),
            'meal_plans_failed' => MealPlan::where('status', 'failed')->count(),
        ];
    }

    private function systemStats(): array
    {
        return [
            'pending_jobs' => DB::table('jobs')->count(),
            'failed_jobs' => DB::table('failed_jobs')->count(),
        ];
    }

    /**
     * Daily counts for the last 14 days for charting.
     */
    private function charts(): array
    {
        $days = collect(range(13, 0))->map(fn ($i) => now()->subDays($i)->startOfDay());

        $signups = $days->map(function ($day) {
            return [
                'date' => $day->format('M j'),
                'value' => User::whereBetween('created_at', [$day, $day->copy()->endOfDay()])->count(),
            ];
        })->values();

        $recipes = $days->map(function ($day) {
            return [
                'date' => $day->format('M j'),
                'value' => Recipe::whereBetween('created_at', [$day, $day->copy()->endOfDay()])->count(),
            ];
        })->values();

        $revenue = $days->map(function ($day) {
            return [
                'date' => $day->format('M j'),
                'value' => $this->revenueSince($day) / 100,
            ];
        })->values();

        return [
            'signups' => $signups,
            'recipes' => $recipes,
            'revenue' => $revenue,
        ];
    }

    private function topUsers(): array
    {
        return User::query()
            ->select(['id', 'uuid', 'name', 'username', 'email', 'token_balance'])
            ->withCount(['recipe as recipes_count'])
            ->orderByDesc('recipes_count')
            ->limit(10)
            ->get()
            ->map(fn ($user) => [
                'uuid' => $user->uuid,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'token_balance' => $user->token_balance,
                'recipes_count' => $user->recipes_count,
            ])
            ->toArray();
    }
}
