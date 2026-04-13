<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TokenTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin token transaction history viewer.
 */
class TokenTransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $query = TokenTransaction::query()
            ->with('user:id,uuid,name,username,email')
            ->latest();

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($search = $request->input('search')) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        $transactions = $query->paginate(50)->withQueryString();

        return Inertia::render('Admin/Tokens/Transactions', [
            'transactions' => $transactions,
            'filters' => [
                'type' => $type,
                'search' => $search,
            ],
        ]);
    }
}
