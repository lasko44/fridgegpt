<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin user management — list and detail views.
 */
class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::query()
            ->select(['id', 'uuid', 'name', 'username', 'email', 'token_balance', 'is_admin', 'email_verified_at', 'created_at']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('username', 'ilike', "%{$search}%");
            });
        }

        if ($request->boolean('admins_only')) {
            $query->where('is_admin', true);
        }

        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'oldest' => $query->oldest(),
            'name' => $query->orderBy('name'),
            'tokens' => $query->orderByDesc('token_balance'),
            default => $query->latest(),
        };

        $users = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'admins_only' => $request->boolean('admins_only'),
            ],
        ]);
    }

    public function show(User $user): Response
    {
        $user->load([
            'tokenTransactions' => fn ($q) => $q->latest()->limit(50),
            'recipe' => fn ($q) => $q->latest()->limit(20),
            'mealPlans' => fn ($q) => $q->latest()->limit(20),
        ]);

        return Inertia::render('Admin/Users/Show', [
            'user' => [
                'uuid' => $user->uuid,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'token_balance' => $user->token_balance,
                'is_admin' => $user->is_admin,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'transactions' => $user->tokenTransactions,
                'recipes' => $user->recipe,
                'meal_plans' => $user->mealPlans,
            ],
        ]);
    }
}
